<?php
defined('SOLICITY_ENTRY') or die('Direct access forbidden.');

/**
 * First-run demo data: one admin, two customers, checking + savings
 * accounts each, a virtual debit card per checking account, and ~60 days
 * of varied transaction history so the dashboard charts have something
 * real to show.
 */
function seed_database(PDO $pdo): void {
    $pdo->beginTransaction();

    $pdo->prepare('INSERT INTO admins (id, username, password_hash, name) VALUES (?,?,?,?)')
        ->execute([new_id('adm_'), 'admin', '$2y$12$NK9p9A77gcQ.XEV8WnRDee3BeP22Y2kapcIVhZNZcvxQVKq7isjBy', 'Platform Admin']);

    $customers = [
        ['name' => 'Ada Idowu',    'email' => 'ada@demo.test',    'phone' => '+1 (312) 555-0148', 'hash' => '$2y$12$UOTTx38C3c2.MYMy7KGrguEUFHxNu2k46wkIGPgeXhIVV6LZbW3Na', 'opening' => ['Checking' => 6800, 'Savings' => 18250]],
        ['name' => 'Marcus Webb',  'email' => 'marcus@demo.test', 'phone' => '+1 (415) 555-0173', 'hash' => '$2y$12$BDIc4fhyE4acj01a01zxieLqq1q35PksebCt2HYNdDuj2O0qB7nfu', 'opening' => ['Checking' => 2400, 'Savings' => 5100]],
    ];

    $categories = [
        ['label' => 'Groceries',     'min' => -140, 'max' => -35],
        ['label' => 'Dining',        'min' => -85,  'max' => -12],
        ['label' => 'Transport',     'min' => -60,  'max' => -8],
        ['label' => 'Shopping',      'min' => -220, 'max' => -20],
        ['label' => 'Utilities',     'min' => -180, 'max' => -60],
        ['label' => 'Entertainment', 'min' => -70,  'max' => -10],
        ['label' => 'Subscriptions', 'min' => -45,  'max' => -8],
        ['label' => 'Health',        'min' => -150, 'max' => -20],
    ];
    $merchants = [
        'Groceries' => ['Whole Foods Market', 'Trader Joe\'s', 'Kroger', 'Local Farmers Market'],
        'Dining' => ['Blue Bottle Coffee', 'Sushi Nakamura', 'The Corner Bistro', 'Chipotle'],
        'Transport' => ['Uber', 'Lyft', 'Metro Transit', 'Shell Gas Station'],
        'Shopping' => ['Amazon', 'Nordstrom', 'Target', 'Best Buy'],
        'Utilities' => ['Pacific Gas & Electric', 'City Water Dept', 'Comcast Internet', 'Verizon Wireless'],
        'Entertainment' => ['Netflix', 'AMC Theatres', 'Spotify', 'Steam'],
        'Subscriptions' => ['iCloud+', 'Adobe Creative Cloud', 'The New York Times', 'Notion'],
        'Health' => ['CVS Pharmacy', 'City Dental', 'Peak Fitness Studio', 'Walgreens'],
    ];

    foreach ($customers as $c) {
        $userId = new_id('usr_');
        $pdo->prepare('INSERT INTO users (id, name, email, phone, password_hash) VALUES (?,?,?,?,?)')
            ->execute([$userId, $c['name'], $c['email'], $c['phone'], $c['hash']]);

        foreach (['Checking', 'Savings'] as $type) {
            $accountId = new_id('acc_');
            $accountNumber = (string) random_int(4400000000, 4499999999);
            $opening = $c['opening'][$type];

            $pdo->prepare('INSERT INTO accounts (id, user_id, type, account_number, balance) VALUES (?,?,?,?,?)')
                ->execute([$accountId, $userId, $type, $accountNumber, $opening]);

            if ($type === 'Checking') {
                $cardId = new_id('crd_');
                $cardNumber = '4' . implode(' ', str_split((string) random_int(100000000000000, 999999999999999), 4));
                $pdo->prepare('INSERT INTO cards (id, account_id, card_number, card_holder, exp_month, exp_year, cvv) VALUES (?,?,?,?,?,?,?)')
                    ->execute([$cardId, $accountId, $cardNumber, strtoupper($c['name']), (int) date('n'), (int) date('Y') + 4, (string) random_int(100, 999)]);
            }

            // ---- transaction history, oldest first, running balance ----
            $days = 60;
            $running = $opening;
            $events = [];

            for ($d = $days; $d >= 0; $d--) {
                $ts = date('Y-m-d H:i:s', strtotime("-{$d} days " . random_int(8, 21) . ':' . random_int(0, 59)));

                // biweekly income into Checking only
                if ($type === 'Checking' && $d % 14 === 0 && $d > 0) {
                    $amount = round(random_int(280000, 320000) / 100, 2);
                    $events[] = ['ts' => $ts, 'type' => 'deposit', 'category' => 'Income', 'desc' => 'Payroll deposit', 'counterparty' => 'Vantage Systems Inc.', 'amount' => $amount];
                }
                // monthly rent out of Checking
                if ($type === 'Checking' && (int) date('j', strtotime($ts)) === 1) {
                    $events[] = ['ts' => $ts, 'type' => 'bill_pay', 'category' => 'Housing', 'desc' => 'Rent payment', 'counterparty' => 'Meridian Property Group', 'amount' => -round(random_int(140000, 190000) / 100, 2)];
                }
                // everyday spending, a few times a week
                if ($type === 'Checking' && random_int(1, 100) <= 45) {
                    $cat = $categories[array_rand($categories)];
                    $amount = round(random_int($cat['min'] * 100, $cat['max'] * 100) / 100, 2);
                    $merchant = $merchants[$cat['label']][array_rand($merchants[$cat['label']])];
                    $events[] = ['ts' => $ts, 'type' => 'withdrawal', 'category' => $cat['label'], 'desc' => $merchant, 'counterparty' => $merchant, 'amount' => $amount];
                }
                // occasional savings top-up
                if ($type === 'Savings' && random_int(1, 100) <= 12) {
                    $amount = round(random_int(10000, 60000) / 100, 2);
                    $events[] = ['ts' => $ts, 'type' => 'deposit', 'category' => 'Savings', 'desc' => 'Transfer from Checking', 'counterparty' => 'Self', 'amount' => $amount];
                }
            }

            usort($events, fn($a, $b) => strcmp($a['ts'], $b['ts']));

            $stmt = $pdo->prepare('INSERT INTO transactions (id, account_id, type, category, description, counterparty, amount, balance_after, created_at) VALUES (?,?,?,?,?,?,?,?,?)');

            // Record the starting balance as a real transaction (dated just
            // before the history below begins) so the account's balance is
            // always fully backed by its transaction history — required for
            // admin edits/deletes, which recompute balances purely from
            // this table.
            if (abs($opening) > 0.001) {
                $openingTs = date('Y-m-d H:i:s', strtotime('-' . ($days + 1) . ' days 09:00'));
                $stmt->execute([new_id('txn_'), $accountId, 'deposit', 'Opening balance', 'Opening balance', 'Solicity Bank', $opening, round($opening, 2), $openingTs]);
            }

            foreach ($events as $e) {
                $running += $e['amount'];
                $stmt->execute([new_id('txn_'), $accountId, $e['type'], $e['category'], $e['desc'], $e['counterparty'], $e['amount'], round($running, 2), $e['ts']]);
            }

            $pdo->prepare('UPDATE accounts SET balance = ? WHERE id = ?')->execute([round($running, 2), $accountId]);
        }
    }

    $pdo->commit();
}
