export default async function run(page, ui) {
  const out = {};

  // Python-style: exercise the exact defect by simulating a slow/lost step-6 POST.
  // We drive the real UI, but intercept the step6 request and let it "fail" the way
  // a slow server would (session not yet persisted when the recap/send-otp fires).

  await page.locator('input[type=radio][value=producer]').first().check({ force: true }).catch(() => {});
  await page.locator('#btn-submit-step-1, [data-step-next="1"]').first().click({ force: true }).catch(() => {});
  await page.waitForTimeout(300);

  await page.fill('#field-first_name', 'Jean').catch(() => {});
  await page.fill('#field-last_name', 'Ngono').catch(() => {});
  const g = page.locator('#field-gender');
  if (await g.count()) {
    const tag = await g.first().evaluate(el => el.tagName);
    if (tag === 'SELECT') await g.selectOption({ index: 1 }).catch(() => {});
    else await g.first().check({ force: true }).catch(() => {});
  }
  await page.fill('#field-date_of_birth', '1990-05-10').catch(() => {});
  await page.fill('#field-phone', '655112233').catch(() => {});
  await page.locator('#btn-submit-step-2, [data-step-next="2"]').first().click({ force: true }).catch(() => {});
  await page.waitForTimeout(700);

  // Fill step 6 correctly (email + pwd + terms)
  await page.locator('#field-email').fill('nyobempayfowen@gmail.com').catch(() => {});
  await page.locator('#field-password').fill('Password123!').catch(() => {});
  await page.locator('#field-password_confirmation').fill('Password123!').catch(() => {});
  await page.locator('#field-accepted_terms').check({ force: true }).catch(() => {});

  // ---- KEY TEST: force the recap to render WITHOUT a successful step6 POST ----
  // This mimics "email displayed but backend has no email".
  await page.evaluate(() => {
    // pretend a previous successful submit stored only the email in sessionStorage
    sessionStorage.setItem('register.step6', JSON.stringify({ email: 'nyobempayfowen@gmail.com' }));
    // and make the upcoming step6 POST fail to be *stored server-side*
  });

  // Block the step6 endpoint so the server-side session is never written,
  // while the UI still advances (the exact mismatch reported).
  await page.route('**/register/step6', route => route.abort());
  await page.locator('#btn-submit-step-6').click({ force: true }).catch(() => {});
  await page.waitForTimeout(800);

  const panelsAfterBlocked = await page.evaluate(() =>
    [...document.querySelectorAll('.step-panel')].filter(p => !p.classList.contains('hidden')).map(p => p.id)
  );
  out.panelsAfterBlockedStep6 = panelsAfterBlocked;

  // If the UI advanced to step 7 anyway, read the recap email (client-side) then hit send-otp
  if (panelsAfterBlocked.includes('panel-step-7')) {
    out.recapEmailShown = await page.locator('#recap-email').innerText().catch(() => '');

    await page.unroute('**/register/step6');
    await page.locator('#btn-submit-step-7').click({ force: true }).catch(() => {});
    await page.waitForTimeout(1200);

    out.alertAfterSendOtp = await page.locator('#register-alert, .alert, [role=alert]').first().innerText().catch(() => '');
    out.panelsAfterSendOtp = await page.evaluate(() =>
      [...document.querySelectorAll('.step-panel')].filter(p => !p.classList.contains('hidden')).map(p => p.id)
    );
  }

  // ---- Now prove bypassing steps 3/4/5 is possible ----
  const fresh = await page.context().newPage();
  await fresh.goto('http://127.0.0.1:8123/register');
  await fresh.waitForTimeout(500);

  // Direct HTTP style: POST step6 then send-otp, skipping steps 3,4,5 entirely.
  const res = await fresh.evaluate(async () => {
    const token = document.querySelector('meta[name=csrf-token]')?.content
      || document.querySelector('input[name=_token]')?.value || '';
    const post = async (url, body, json = true) => {
      const r = await fetch(url, {
        method: 'POST',
        headers: json
          ? { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }
          : { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        body,
      });
      return { status: r.status, body: (await r.text()).slice(0, 200) };
    };

    const role = await post('/register/step1', JSON.stringify({ role: 'producer' }));

    const fd = new FormData();
    fd.append('email', 'skip.test@example.test');
    fd.append('password', 'Password123!');
    fd.append('password_confirmation', 'Password123!');
    fd.append('accepted_terms', '1');
    const step6skip = await post('/register/step6', fd, false);

    const otp = await post('/register/send-otp', JSON.stringify({}));

    const fin = await post('/register/step8', JSON.stringify({ otp: '123456' }));

    return { role, step6skip, otp, fin };
  });

  out.skipSteps345 = res;
  await fresh.close();

  return out;
}
