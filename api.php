<?php
/**
 * Solicity Bank — API dispatcher. Every mutating action goes through
 * here and touches the real SQLite database via PDO, wrapped in a
 * transaction so multi-row updates (a transfer debits one account and
 * credits another) either both apply or neither does.
 */
define('SOLICITY_ENTRY', true);
require_once dirname(__DIR__) . '/lib/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');

$action = $_POST['action'] ?? '';
$back = $_POST['redirect'] ?? 'index.php';

function fail(string $back, string $msg): never { flash_set('error', $msg); redirect($back); }
function ok(string $back, string $msg): never { flash_set('success', $msg); redirect($back); }

if ($action !== 'register' && !csrf_check()) {
    fail($back, 'Your session expired — please try again.');
}
if ($action === 'register' && !csrf_check()) {
    fail('register.php', 'Your session expired — please try again.');
}

$pdo = db();

function account_belongs_to(PDO $pdo, string $accountId, string $userId): ?array {
    $stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ? AND user_id = ?');
    $stmt->execute([$accountId, $userId]);
    $a = $stmt->fetch();
    return $a ?: null;
}

function record_tx(PDO $pdo, string $accountId, string $type, string $category, string $desc, ?string $counterparty, float $amount, float $balanceAfter): string {
    $id = new_id('txn_');
    $pdo->prepare('INSERT INTO transactions (id, account_id, type, category, description, counterparty, amount, balance_after) VALUES (?,?,?,?,?,?,?,?)')
        ->execute([$id, $accountId, $type, $category, $desc, $counterparty, $amount, round($balanceAfter, 2)]);
    return $id;
}

// Replays every transaction on an account in chronological order to
// recompute each row's running balance_after, and updates the account's
// current balance to match the final total. Called any time admin edits,
// deletes, or inserts a transaction so the history stays internally
// consistent instead of drifting from the account balance.
function recompute_account_balances(PDO $pdo, string $accountId): void {
    $stmt = $pdo->prepare('SELECT id, amount FROM transactions WHERE account_id = ? ORDER BY created_at ASC, id ASC');
    $stmt->execute([$accountId]);
    $rows = $stmt->fetchAll();

    $running = 0.0;
    $update = $pdo->prepare('UPDATE transactions SET balance_after = ? WHERE id = ?');
    foreach ($rows as $row) {
        $running += (float) $row['amount'];
        $update->execute([round($running, 2), $row['id']]);
    }
    $pdo->prepare('UPDATE accounts SET balance = ? WHERE id = ?')->execute([round($running, 2), $accountId]);
}

switch ($action) {

    // ---------------------------------------------------------- AUTH ----
    case 'register': {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';

        if (strlen($name) < 2) fail('register.php', 'Enter your full name.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('register.php', 'Enter a valid email address.');
        if (strlen($password) < 6) fail('register.php', 'Choose a password with at least 6 characters.');

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) fail('register.php', 'An account with that email already exists.');

        try {
            $pdo->beginTransaction();
            $userId = new_id('usr_');
            $pdo->prepare('INSERT INTO users (id, name, email, phone, password_hash) VALUES (?,?,?,?,?)')
                ->execute([$userId, $name, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]);

            $accountId = new_id('acc_');
            $accountNumber = (string) random_int(4400000000, 4499999999);
            $openingBonus = 250.00;
            $pdo->prepare('INSERT INTO accounts (id, user_id, type, account_number, balance) VALUES (?,?,?,?,?)')
                ->execute([$accountId, $userId, 'Checking', $accountNumber, $openingBonus]);
            record_tx($pdo, $accountId, 'deposit', 'Welcome bonus', 'Welcome to Solicity Bank', 'Solicity Bank', $openingBonus, $openingBonus);

            $savingsId = new_id('acc_');
            $savingsNumber = (string) random_int(4400000000, 4499999999);
            $pdo->prepare('INSERT INTO accounts (id, user_id, type, account_number, balance) VALUES (?,?,?,?,?)')
                ->execute([$savingsId, $userId, 'Savings', $savingsNumber, 0]);

            $cardId = new_id('crd_');
            $cardNumber = '4' . implode(' ', str_split((string) random_int(100000000000000, 999999999999999), 4));
            $pdo->prepare('INSERT INTO cards (id, account_id, card_number, card_holder, exp_month, exp_year, cvv) VALUES (?,?,?,?,?,?,?)')
                ->execute([$cardId, $accountId, $cardNumber, strtoupper($name), (int) date('n'), (int) date('Y') + 4, (string) random_int(100, 999)]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            fail('register.php', 'Something went wrong creating your account. Please try again.');
        }

        login_customer($userId);
        ok('app/dashboard.php?welcome=1', 'Welcome to Solicity Bank, ' . explode(' ', $name)[0] . '. A $250 welcome bonus has been added to your new Checking account.');
    }

    case 'login_customer': {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user || !verify_password($password, $user['password_hash'])) {
            fail('login.php', 'Incorrect email or password.');
        }
        login_customer($user['id']);
        ok('app/dashboard.php', 'Welcome back, ' . explode(' ', $user['name'])[0] . '.');
    }

    case 'login_admin': {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ?');
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        if (!$admin || !verify_password($password, $admin['password_hash'])) {
            fail($back, 'Incorrect admin username or password.');
        }
        login_admin($admin['id']);
        ok('admin/index.php', 'Signed in as ' . $admin['name'] . '.');
    }

    case 'logout': {
        logout_all();
        ok('index.php', 'You have been signed out.');
    }

    // ------------------------------------------------------- TRANSFERS ----
    case 'transfer_own': {
        $user = require_customer();
        $fromId = $_POST['from'] ?? '';
        $toId = $_POST['to'] ?? '';
        $amount = round((float) ($_POST['amount'] ?? 0), 2);
        if ($fromId === $toId) fail($back, 'Choose two different accounts.');
        if ($amount <= 0) fail($back, 'Enter an amount greater than zero.');

        $from = account_belongs_to($pdo, $fromId, $user['id']);
        $to = account_belongs_to($pdo, $toId, $user['id']);
        if (!$from || !$to) fail($back, 'Account not found.');
        if ($from['balance'] < $amount) fail($back, 'Insufficient funds in that account.');

        try {
            $pdo->beginTransaction();
            $newFromBal = $from['balance'] - $amount;
            $newToBal = $to['balance'] + $amount;
            $pdo->prepare('UPDATE accounts SET balance = ? WHERE id = ?')->execute([$newFromBal, $from['id']]);
            $pdo->prepare('UPDATE accounts SET balance = ? WHERE id = ?')->execute([$newToBal, $to['id']]);
            record_tx($pdo, $from['id'], 'transfer_out', 'Transfer', "Transfer to {$to['type']}", 'Self', -$amount, $newFromBal);
            record_tx($pdo, $to['id'], 'transfer_in', 'Transfer', "Transfer from {$from['type']}", 'Self', $amount, $newToBal);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            fail($back, 'Transfer failed. Please try again.');
        }
        ok($back, 'Transferred ' . money($amount) . ' from ' . $from['type'] . ' to ' . $to['type'] . '.');
    }

    case 'transfer_peer': {
        $user = require_customer();
        $fromId = $_POST['from'] ?? '';
        $toEmail = trim($_POST['to_email'] ?? '');
        $amount = round((float) ($_POST['amount'] ?? 0), 2);
        if ($amount <= 0) fail($back, 'Enter an amount greater than zero.');

        $from = account_belongs_to($pdo, $fromId, $user['id']);
        if (!$from) fail($back, 'Source account not found.');
        if ($from['balance'] < $amount) fail($back, 'Insufficient funds.');

        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$toEmail]);
        $recipient = $stmt->fetch();
        if (!$recipient) fail($back, 'No Solicity Bank customer with that email.');
        if ($recipient['id'] === $user['id']) fail($back, 'Use "Between my accounts" to move your own money.');

        $stmt = $pdo->prepare("SELECT * FROM accounts WHERE user_id = ? AND type = 'Checking' LIMIT 1");
        $stmt->execute([$recipient['id']]);
        $recipientAcc = $stmt->fetch();
        if (!$recipientAcc) fail($back, 'Recipient has no active account.');

        try {
            $pdo->beginTransaction();
            $newFromBal = $from['balance'] - $amount;
            $newToBal = $recipientAcc['balance'] + $amount;
            $pdo->prepare('UPDATE accounts SET balance = ? WHERE id = ?')->execute([$newFromBal, $from['id']]);
            $pdo->prepare('UPDATE accounts SET balance = ? WHERE id = ?')->execute([$newToBal, $recipientAcc['id']]);
            record_tx($pdo, $from['id'], 'transfer_out', 'Transfer', 'Sent to ' . $recipient['name'], $recipient['name'], -$amount, $newFromBal);
            record_tx($pdo, $recipientAcc['id'], 'transfer_in', 'Transfer', 'Received from ' . $user['name'], $user['name'], $amount, $newToBal);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            fail($back, 'Transfer failed. Please try again.');
        }
        ok($back, 'Sent ' . money($amount) . ' to ' . e($recipient['name']) . '.');
    }

    case 'transfer_external': {
        $user = require_customer();
        $fromId = $_POST['from'] ?? '';
        $bankName = trim($_POST['bank_name'] ?? '');
        $accountName = trim($_POST['account_name'] ?? '');
        $accountNumber = trim($_POST['account_number'] ?? '');
        $routingNumber = trim($_POST['routing_number'] ?? '');
        $swiftCode = trim(strtoupper($_POST['swift_code'] ?? ''));
        $amount = round((float) ($_POST['amount'] ?? 0), 2);

        if ($amount <= 0) fail($back, 'Enter an amount greater than zero.');
        if ($bankName === '') fail($back, 'Enter the receiving bank\'s name.');
        if ($accountName === '') fail($back, 'Enter the name on the receiving account.');
        if ($accountNumber === '') fail($back, 'Enter the receiving account number.');
        if ($routingNumber === '' && $swiftCode === '') fail($back, 'Enter a routing number or a SWIFT/BIC code.');

        $from = account_belongs_to($pdo, $fromId, $user['id']);
        if (!$from) fail($back, 'Account not found.');
        if ($from['balance'] < $amount) fail($back, 'Insufficient funds in that account.');

        try {
            $pdo->beginTransaction();
            $newBal = $from['balance'] - $amount;
            $pdo->prepare('UPDATE accounts SET balance = ? WHERE id = ?')->execute([$newBal, $from['id']]);
            $txId = record_tx($pdo, $from['id'], 'transfer_external', 'External Transfer', 'External transfer to ' . $bankName, $accountName, -$amount, $newBal);
            $pdo->prepare('INSERT INTO external_transfers (id, transaction_id, bank_name, account_name, account_number, routing_number, swift_code) VALUES (?,?,?,?,?,?,?)')
                ->execute([new_id('ext_'), $txId, $bankName, $accountName, $accountNumber, $routingNumber ?: null, $swiftCode ?: null]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            fail($back, 'Transfer failed. Please try again.');
        }
        ok($back, 'Sent ' . money($amount) . ' to ' . e($accountName) . ' at ' . e($bankName) . '. External transfers typically take 1–3 business days to settle.');
    }

    case 'pay_bill': {
        $user = require_customer();
        $fromId = $_POST['from'] ?? '';
        $payee = trim($_POST['payee'] ?? '');
        $amount = round((float) ($_POST['amount'] ?? 0), 2);
        if ($amount <= 0) fail($back, 'Enter an amount greater than zero.');
        if ($payee === '') fail($back, 'Choose a payee.');

        $from = account_belongs_to($pdo, $fromId, $user['id']);
        if (!$from) fail($back, 'Account not found.');
        if ($from['balance'] < $amount) fail($back, 'Insufficient funds.');

        try {
            $pdo->beginTransaction();
            $newBal = $from['balance'] - $amount;
            $pdo->prepare('UPDATE accounts SET balance = ? WHERE id = ?')->execute([$newBal, $from['id']]);
            record_tx($pdo, $from['id'], 'bill_pay', 'Bill Payment', 'Payment to ' . $payee, $payee, -$amount, $newBal);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            fail($back, 'Payment failed. Please try again.');
        }
        ok($back, 'Paid ' . money($amount) . ' to ' . e($payee) . '.');
    }

    // ----------------------------------------------------------- CARDS ----
    case 'toggle_card': {
        $user = require_customer();
        $cardId = $_POST['card_id'] ?? '';
        $stmt = $pdo->prepare('SELECT cards.* FROM cards JOIN accounts ON cards.account_id = accounts.id WHERE cards.id = ? AND accounts.user_id = ?');
        $stmt->execute([$cardId, $user['id']]);
        $card = $stmt->fetch();
        if (!$card) fail($back, 'Card not found.');
        $newStatus = $card['status'] === 'active' ? 'frozen' : 'active';
        $pdo->prepare('UPDATE cards SET status = ? WHERE id = ?')->execute([$newStatus, $cardId]);
        ok($back, $newStatus === 'frozen' ? 'Your card has been frozen.' : 'Your card is active again.');
    }

    // ---------------------------------------------------------- PROFILE ----
    case 'update_profile': {
        $user = require_customer();
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        if (strlen($name) < 2) fail($back, 'Enter your full name.');
        $pdo->prepare('UPDATE users SET name = ?, phone = ? WHERE id = ?')->execute([$name, $phone, $user['id']]);
        ok($back, 'Profile updated.');
    }

    case 'change_password': {
        $user = require_customer();
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        if (!verify_password($current, $user['password_hash'])) fail($back, 'Current password is incorrect.');
        if (strlen($new) < 6) fail($back, 'New password must be at least 6 characters.');
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
        ok($back, 'Password changed.');
    }

    // ------------------------------------------------------------ ADMIN ----
    case 'admin_set_balance': {
        require_admin();
        $accountId = $_POST['account_id'] ?? '';
        $newBalance = round((float) ($_POST['balance'] ?? 0), 2);
        $stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
        $stmt->execute([$accountId]);
        $acc = $stmt->fetch();
        if (!$acc) fail($back, 'Account not found.');
        $diff = $newBalance - $acc['balance'];
        $pdo->prepare('UPDATE accounts SET balance = ? WHERE id = ?')->execute([$newBalance, $accountId]);
        record_tx($pdo, $accountId, 'adjustment', 'Admin', 'Admin balance adjustment', 'Solicity Bank Admin', $diff, $newBalance);
        ok($back, 'Balance updated.');
    }

    case 'admin_toggle_account': {
        require_admin();
        $accountId = $_POST['account_id'] ?? '';
        $stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
        $stmt->execute([$accountId]);
        $acc = $stmt->fetch();
        if (!$acc) fail($back, 'Account not found.');
        $newStatus = $acc['status'] === 'active' ? 'frozen' : 'active';
        $pdo->prepare('UPDATE accounts SET status = ? WHERE id = ?')->execute([$newStatus, $accountId]);
        ok($back, 'Account is now ' . $newStatus . '.');
    }

    case 'admin_toggle_user': {
        require_admin();
        $userId = $_POST['user_id'] ?? '';
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $u = $stmt->fetch();
        if (!$u) fail($back, 'Customer not found.');
        $newStatus = $u['status'] === 'active' ? 'frozen' : 'active';
        $pdo->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$newStatus, $userId]);
        ok($back, 'Customer is now ' . $newStatus . '.');
    }

    case 'admin_create_customer': {
        require_admin();
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $status = ($_POST['status'] ?? 'active') === 'frozen' ? 'frozen' : 'active';
        $checkingBal = round((float) ($_POST['checking_balance'] ?? 0), 2);
        $savingsBal = round((float) ($_POST['savings_balance'] ?? 0), 2);

        if (strlen($name) < 2) fail($back, 'Enter the customer\'s full name.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail($back, 'Enter a valid email address.');
        if (strlen($password) < 6) fail($back, 'Choose a password with at least 6 characters.');
        if ($checkingBal < 0 || $savingsBal < 0) fail($back, 'Opening balances can\'t be negative.');

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) fail($back, 'A customer with that email already exists.');

        try {
            $pdo->beginTransaction();
            $userId = new_id('usr_');
            $pdo->prepare('INSERT INTO users (id, name, email, phone, password_hash, status) VALUES (?,?,?,?,?,?)')
                ->execute([$userId, $name, $email, $phone, password_hash($password, PASSWORD_DEFAULT), $status]);

            $accountId = new_id('acc_');
            $accountNumber = (string) random_int(4400000000, 4499999999);
            $pdo->prepare('INSERT INTO accounts (id, user_id, type, account_number, balance) VALUES (?,?,?,?,?)')
                ->execute([$accountId, $userId, 'Checking', $accountNumber, $checkingBal]);
            if ($checkingBal > 0) record_tx($pdo, $accountId, 'deposit', 'Opening balance', 'Opening balance', 'Solicity Bank Admin', $checkingBal, $checkingBal);

            $savingsId = new_id('acc_');
            $savingsNumber = (string) random_int(4400000000, 4499999999);
            $pdo->prepare('INSERT INTO accounts (id, user_id, type, account_number, balance) VALUES (?,?,?,?,?)')
                ->execute([$savingsId, $userId, 'Savings', $savingsNumber, $savingsBal]);
            if ($savingsBal > 0) record_tx($pdo, $savingsId, 'deposit', 'Opening balance', 'Opening balance', 'Solicity Bank Admin', $savingsBal, $savingsBal);

            $cardId = new_id('crd_');
            $cardNumber = '4' . implode(' ', str_split((string) random_int(100000000000000, 999999999999999), 4));
            $pdo->prepare('INSERT INTO cards (id, account_id, card_number, card_holder, exp_month, exp_year, cvv) VALUES (?,?,?,?,?,?,?)')
                ->execute([$cardId, $accountId, $cardNumber, strtoupper($name), (int) date('n'), (int) date('Y') + 4, (string) random_int(100, 999)]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            fail($back, 'Something went wrong creating that customer. Please try again.');
        }
        ok($back, 'Customer ' . e($name) . ' created with a Checking and Savings account.');
    }

    case 'admin_update_customer': {
        require_admin();
        $userId = $_POST['user_id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $status = ($_POST['status'] ?? 'active') === 'frozen' ? 'frozen' : 'active';

        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $u = $stmt->fetch();
        if (!$u) fail($back, 'Customer not found.');

        if (strlen($name) < 2) fail($back, 'Enter the customer\'s full name.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail($back, 'Enter a valid email address.');

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) fail($back, 'Another customer already uses that email.');

        $pdo->prepare('UPDATE users SET name = ?, email = ?, phone = ?, status = ? WHERE id = ?')
            ->execute([$name, $email, $phone, $status, $userId]);
        ok($back, 'Customer details updated.');
    }

    case 'admin_reset_password': {
        require_admin();
        $userId = $_POST['user_id'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        if (!$stmt->fetch()) fail($back, 'Customer not found.');
        if (strlen($newPassword) < 6) fail($back, 'New password must be at least 6 characters.');
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
        ok($back, 'Password reset for that customer.');
    }

    case 'admin_delete_customer': {
        require_admin();
        $userId = $_POST['user_id'] ?? '';
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $u = $stmt->fetch();
        if (!$u) fail('admin/customers.php', 'Customer not found.');

        try {
            $pdo->beginTransaction();
            $accIds = array_column(user_accounts($userId), 'id');
            if ($accIds) {
                $ph = implode(',', array_fill(0, count($accIds), '?'));
                $pdo->prepare("DELETE FROM transactions WHERE account_id IN ($ph)")->execute($accIds);
                $pdo->prepare("DELETE FROM cards WHERE account_id IN ($ph)")->execute($accIds);
            }
            $pdo->prepare('DELETE FROM accounts WHERE user_id = ?')->execute([$userId]);
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            fail('admin/customers.php', 'Could not delete that customer. Please try again.');
        }
        ok('admin/customers.php', e($u['name']) . ' and all their accounts have been deleted.');
    }

    case 'admin_add_transaction': {
        require_admin();
        $accountId = $_POST['account_id'] ?? '';
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? '') ?: 'General';
        $amount = round((float) ($_POST['amount'] ?? 0), 2);
        $createdAt = trim($_POST['created_at'] ?? '');

        $stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
        $stmt->execute([$accountId]);
        $acc = $stmt->fetch();
        if (!$acc) fail($back, 'Account not found.');
        if ($description === '') fail($back, 'Enter a description.');
        if ($amount == 0) fail($back, 'Amount can\'t be zero.');

        $ts = $createdAt !== '' ? str_replace('T', ' ', $createdAt) . ':00' : now_iso();

        try {
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO transactions (id, account_id, type, category, description, counterparty, amount, balance_after, created_at) VALUES (?,?,?,?,?,?,?,0,?)')
                ->execute([new_id('txn_'), $accountId, 'manual', $category, $description, 'Solicity Bank Admin', $amount, $ts]);
            recompute_account_balances($pdo, $accountId);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            fail($back, 'Could not add that transaction. Please try again.');
        }
        ok($back, 'Transaction added.');
    }

    case 'admin_update_transaction': {
        require_admin();
        $txId = $_POST['transaction_id'] ?? '';
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? '') ?: 'General';
        $amount = round((float) ($_POST['amount'] ?? 0), 2);
        $createdAt = trim($_POST['created_at'] ?? '');

        $stmt = $pdo->prepare('SELECT * FROM transactions WHERE id = ?');
        $stmt->execute([$txId]);
        $tx = $stmt->fetch();
        if (!$tx) fail($back, 'Transaction not found.');
        if ($description === '') fail($back, 'Enter a description.');
        if ($amount == 0) fail($back, 'Amount can\'t be zero.');

        $ts = $createdAt !== '' ? str_replace('T', ' ', $createdAt) . ':00' : $tx['created_at'];

        try {
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE transactions SET description = ?, category = ?, amount = ?, created_at = ? WHERE id = ?')
                ->execute([$description, $category, $amount, $ts, $txId]);
            recompute_account_balances($pdo, $tx['account_id']);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            fail($back, 'Could not update that transaction. Please try again.');
        }
        ok($back, 'Transaction updated.');
    }

    case 'admin_delete_transaction': {
        require_admin();
        $txId = $_POST['transaction_id'] ?? '';
        $stmt = $pdo->prepare('SELECT * FROM transactions WHERE id = ?');
        $stmt->execute([$txId]);
        $tx = $stmt->fetch();
        if (!$tx) fail($back, 'Transaction not found.');

        try {
            $pdo->beginTransaction();
            $pdo->prepare('DELETE FROM transactions WHERE id = ?')->execute([$txId]);
            recompute_account_balances($pdo, $tx['account_id']);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            fail($back, 'Could not delete that transaction. Please try again.');
        }
        ok($back, 'Transaction deleted.');
    }

    case 'admin_issue_card': {
        require_admin();
        $accountId = $_POST['account_id'] ?? '';
        $stmt = $pdo->prepare('SELECT a.*, u.name as holder_name FROM accounts a JOIN users u ON a.user_id = u.id WHERE a.id = ?');
        $stmt->execute([$accountId]);
        $acc = $stmt->fetch();
        if (!$acc) fail($back, 'Account not found.');
        if (account_card($accountId)) fail($back, 'This account already has a card.');

        $cardId = new_id('crd_');
        $cardNumber = '4' . implode(' ', str_split((string) random_int(100000000000000, 999999999999999), 4));
        $pdo->prepare('INSERT INTO cards (id, account_id, card_number, card_holder, exp_month, exp_year, cvv) VALUES (?,?,?,?,?,?,?)')
            ->execute([$cardId, $accountId, $cardNumber, strtoupper($acc['holder_name']), (int) date('n'), (int) date('Y') + 4, (string) random_int(100, 999)]);
        ok($back, 'Card issued for ' . $acc['type'] . '.');
    }

    default:
        fail($back, 'Unknown action.');
}
