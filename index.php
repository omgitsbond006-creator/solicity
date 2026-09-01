<?php
define('SOLICITY_ENTRY', true);
require_once __DIR__ . '/lib/config.php';

if (current_user()) redirect('app/dashboard.php');

$page_title = 'Private Banking, Reimagined';
require __DIR__ . '/lib/partials/site-header.php';
?>

<section class="hero">
  <div class="mesh"></div>
  <div class="hero-inner">
    <div class="reveal in">
      <div class="eyebrow">Solicity Bank &middot; Member FDIC (simulated)</div>
      <h1>Banking that feels as good as it looks.</h1>
      <p class="lede">Checking, savings, and a card that moves at the speed of your life — with real-time balances, instant transfers, and a dashboard built for people who actually look at their money.</p>
      <div class="hero-actions">
        <a class="btn" href="<?= e(url('register.php')) ?>">Open an account &nbsp;&rarr;</a>
        <a class="btn ghost" href="<?= e(url('login.php')) ?>">Sign in</a>
      </div>
      <div class="trustline">
        <span><i class="dot-ok"></i> Bank-grade encryption</span>
        <span><i class="dot-ok"></i> No hidden fees</span>
        <span><i class="dot-ok"></i> $250 welcome bonus</span>
      </div>
    </div>
    <div class="card-stage reveal-scale in">
      <div class="credit-card">
        <div class="row-top">
          <div class="chip"></div>
          <div class="brandmark">Solicity</div>
        </div>
        <div class="number">4118&nbsp; 22•• &nbsp;••••&nbsp; 7042</div>
        <div class="row-bottom">
          <div class="field"><div class="lbl">Card Holder</div><div class="val">A. IDOWU</div></div>
          <div class="field"><div class="lbl">Expires</div><div class="val">08/30</div></div>
          <div class="network">VISA</div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="marquee-wrap reveal">
  <div class="marquee-label">Trusted by people who work at</div>
  <div class="marquee-track">
    <span>Northwind Studio</span><span>Vantage Systems</span><span>Harborline Logistics</span><span>Cobalt &amp; Reed</span><span>Alder Peak Ventures</span><span>Meridian Property Group</span>
    <span>Northwind Studio</span><span>Vantage Systems</span><span>Harborline Logistics</span><span>Cobalt &amp; Reed</span><span>Alder Peak Ventures</span><span>Meridian Property Group</span>
  </div>
</div>

<div class="stat-row" data-stagger>
  <div class="stat reveal"><div class="num"><span data-count="240000" data-prefix="$">0</span>+</div><div class="lbl">Managed for customers</div></div>
  <div class="stat reveal"><div class="num"><span data-count="18000" data-suffix="+">0</span></div><div class="lbl">Accounts opened</div></div>
  <div class="stat reveal"><div class="num"><span data-count="99.98" data-decimals="2" data-suffix="%">0</span></div><div class="lbl">Uptime, always on</div></div>
  <div class="stat reveal"><div class="num"><span data-count="4.9" data-decimals="1">0</span>/5</div><div class="lbl">Average customer rating</div></div>
</div>

<section class="section" id="product">
  <div class="section-head reveal">
    <div class="eyebrow center">See it in action</div>
    <h2>A dashboard that actually shows you something.</h2>
    <p>Real balance trends, real spending breakdowns, updated the moment a transaction happens — not a screenshot from last month's statement.</p>
  </div>
  <div class="browser-frame reveal-scale">
    <div class="chrome">
      <span class="dot"></span><span class="dot"></span><span class="dot"></span>
      <div class="urlbar">solicitybank.app/app/dashboard.php</div>
    </div>
    <img src="<?= e(asset('img/dashboard-preview.png')) ?>" alt="Solicity Bank customer dashboard" loading="lazy">
  </div>
</section>

<section class="section" id="how-it-works">
  <div class="section-head reveal">
    <div class="eyebrow center">How it works</div>
    <h2>Open, fund, and move money — in that order, in minutes.</h2>
  </div>
  <div class="steps-row" data-stagger>
    <div class="step reveal">
      <div class="step-num">1</div>
      <h3>Open an account</h3>
      <p>Name, email, and a password. No branch visit, no paper forms, no waiting for a card in the mail to get started.</p>
    </div>
    <div class="step reveal">
      <div class="step-num">2</div>
      <h3>Get funded instantly</h3>
      <p>A $250 welcome bonus lands in your new Checking account the second you sign up, plus a virtual card ready to use.</p>
    </div>
    <div class="step reveal">
      <div class="step-num">3</div>
      <h3>Move money your way</h3>
      <p>Transfer between your own accounts, send to another Solicity customer, or pay a bill — all settle in real time.</p>
    </div>
  </div>
</section>

<section class="section" id="features">
  <div class="section-head reveal">
    <div class="eyebrow center">What you get</div>
    <h2>Everything a modern bank should be.</h2>
    <p>No branch visits, no paperwork, no waiting three days for a transfer to "process." Just your money, moving when you tell it to.</p>
  </div>
  <div class="feature-grid" data-stagger>
    <div class="feature reveal">
      <div class="icon"><?= icon('bolt') ?></div>
      <h3>Instant transfers</h3>
      <p>Move money between your own accounts or to any Solicity customer in real time — no holds, no waiting.</p>
    </div>
    <div class="feature reveal">
      <div class="icon"><?= icon('chart') ?></div>
      <h3>Spending intelligence</h3>
      <p>See exactly where your money goes with live category breakdowns and balance trends, not a monthly PDF.</p>
    </div>
    <div class="feature reveal">
      <div class="icon"><?= icon('card') ?></div>
      <h3>A card you control</h3>
      <p>Freeze it from your phone the moment something feels off. Unfreeze it just as fast.</p>
    </div>
    <div class="feature reveal">
      <div class="icon"><?= icon('shield') ?></div>
      <h3>Serious security</h3>
      <p>Encrypted at rest and in transit, with account-level activity logs so nothing happens without a record.</p>
    </div>
    <div class="feature reveal">
      <div class="icon"><?= icon('bank') ?></div>
      <h3>Checking &amp; savings, together</h3>
      <p>Two accounts that actually talk to each other. Move money between them in a tap, always in sync.</p>
    </div>
    <div class="feature reveal">
      <div class="icon"><?= icon('users') ?></div>
      <h3>Built for real life</h3>
      <p>Pay bills, split costs with friends, and track every dollar without spreadsheets or sticky notes.</p>
    </div>
  </div>
</section>

<section class="section" id="compare">
  <div class="section-head reveal">
    <div class="eyebrow center">Solicity vs. traditional banking</div>
    <h2>The difference shows up on day one.</h2>
  </div>
  <div class="compare-table reveal glass">
    <table>
      <thead>
        <tr><th></th><th class="hl">Solicity Bank</th><th>Traditional bank</th></tr>
      </thead>
      <tbody>
        <tr><td>Account opening</td><td class="yes">Under 2 minutes, online</td><td class="no">Branch visit or 3&ndash;5 days</td></tr>
        <tr><td>Transfer speed</td><td class="yes">Instant, real time</td><td class="no">1&ndash;3 business days</td></tr>
        <tr><td>Monthly fees</td><td class="yes">$0</td><td class="no">$5&ndash;$25</td></tr>
        <tr><td>Card controls</td><td class="yes">Freeze/unfreeze instantly</td><td class="no">Call support</td></tr>
        <tr><td>Spending insight</td><td class="yes">Live, by category</td><td class="no">Monthly PDF statement</td></tr>
      </tbody>
    </table>
  </div>
</section>

<section class="section" id="security">
  <div class="section-head reveal">
    <div class="eyebrow center">What customers say</div>
    <h2>People notice the difference.</h2>
  </div>
  <div class="testimonial-grid" data-stagger>
    <div class="testimonial-card reveal">
      <div class="stars">★★★★★</div>
      <blockquote>&ldquo;I moved my entire direct deposit to Solicity in the first week. It's the first bank account that's ever made me want to check my balance.&rdquo;</blockquote>
      <div class="who"><div class="avatar" style="width:34px;height:34px;font-size:.7rem;">AN</div><div><div class="name">Adaeze N.</div><div class="role">Customer since 2025</div></div></div>
    </div>
    <div class="testimonial-card reveal">
      <div class="stars">★★★★★</div>
      <blockquote>&ldquo;Froze my card from an Uber the second I noticed a weird charge. Unfroze it two minutes later once I figured out it was mine. That alone sold me.&rdquo;</blockquote>
      <div class="who"><div class="avatar" style="width:34px;height:34px;font-size:.7rem;">MW</div><div><div class="name">Marcus W.</div><div class="role">Customer since 2025</div></div></div>
    </div>
    <div class="testimonial-card reveal">
      <div class="stars">★★★★★</div>
      <blockquote>&ldquo;The spending breakdown is the first budgeting tool I've actually kept using. Everything else I tried, I abandoned after a week.&rdquo;</blockquote>
      <div class="who"><div class="avatar" style="width:34px;height:34px;font-size:.7rem;">JE</div><div><div class="name">Jordan E.</div><div class="role">Customer since 2026</div></div></div>
    </div>
  </div>
</section>

<section class="section" id="faq">
  <div class="section-head reveal">
    <div class="eyebrow center">Questions</div>
    <h2>Everything you'd want to ask.</h2>
  </div>
  <div class="faq-list reveal">
    <div class="faq-item">
      <button class="faq-q">Is my money actually safe with Solicity? <span class="chev">&#9662;</span></button>
      <div class="faq-a"><p>Solicity is a demonstration banking platform built to showcase what a modern account experience can look like — no real funds are held or moved. In a production deployment, Solicity would carry the same deposit protections and encryption standards described throughout this site.</p></div>
    </div>
    <div class="faq-item">
      <button class="faq-q">How fast are transfers, really? <span class="chev">&#9662;</span></button>
      <div class="faq-a"><p>Transfers between your own accounts, to another Solicity customer, or bill payments all settle immediately — the balance updates the moment you confirm, no multi-day hold.</p></div>
    </div>
    <div class="faq-item">
      <button class="faq-q">What happens when I freeze my card? <span class="chev">&#9662;</span></button>
      <div class="faq-a"><p>Freezing a card blocks it instantly from your account settings. Unfreezing is just as immediate — no phone call, no waiting on hold.</p></div>
    </div>
    <div class="faq-item">
      <button class="faq-q">Is there a minimum balance or monthly fee? <span class="chev">&#9662;</span></button>
      <div class="faq-a"><p>No minimum balance and no monthly maintenance fee on Checking or Savings accounts.</p></div>
    </div>
    <div class="faq-item">
      <button class="faq-q">Can I have more than one account? <span class="chev">&#9662;</span></button>
      <div class="faq-a"><p>Every customer gets a Checking and a Savings account automatically at signup, and can move money freely between the two.</p></div>
    </div>
  </div>
</section>

<div class="cta-band reveal">
  <div>
    <h2 style="margin-bottom:.3rem;">Ready to see your money differently?</h2>
    <p class="mb-0">Open an account in under two minutes. No minimum balance, no monthly fee.</p>
  </div>
  <a class="btn" href="<?= e(url('register.php')) ?>">Open an account &nbsp;&rarr;</a>
</div>

<?php require __DIR__ . '/lib/partials/site-footer.php'; ?>
