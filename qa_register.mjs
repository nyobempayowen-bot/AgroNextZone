export default async function run(page, ui) {
  const log = [];
  const errors = [];

  page.on('console', m => { if (m.type() === 'error') errors.push(m.text()); });

  // ---------- STEP 1 : role ----------
  let snap = await ui.snapshot();
  log.push('step1 snapshot has producer radio: ' + /producteur/i.test(snap));

  // Choose Producer then continue
  const producerRadio = page.locator('input[type=radio][value=producer]').first();
  if (await producerRadio.count()) {
    await producerRadio.check({ force: true });
  } else {
    // fall back to a labelled card/button
    const prod = page.getByText(/producteur/i).first();
    await prod.click({ force: true }).catch(() => {});
  }
  const btn1 = page.locator('#btn-submit-step-1, [data-step-next="1"]').first();
  await btn1.click({ force: true }).catch(() => {});
  await page.waitForTimeout(400);

  // ---------- STEP 2 : identity ----------
  await page.fill('#field-first_name', 'Jean').catch(() => {});
  await page.fill('#field-last_name', 'Ngono').catch(() => {});
  const genderSel = page.locator('#field-gender');
  if (await genderSel.count()) {
    const tag = await genderSel.evaluate(el => el.tagName);
    if (tag === 'SELECT') await genderSel.selectOption({ index: 1 }).catch(() => {});
    else await genderSel.check({ force: true }).catch(() => {});
  }
  await page.fill('#field-date_of_birth', '1990-05-10').catch(() => {});
  await page.fill('#field-phone', '655112233').catch(() => {});
  await page.locator('#btn-submit-step-2, [data-step-next="2"]').first().click({ force: true }).catch(() => {});
  await page.waitForTimeout(600);

  // ---------- STEP 3 : location ----------
  await page.locator('#field-country').fill('Cameroun').catch(() => {});
  await page.locator('#field-region').selectOption({ index: 1 }).catch(() => {});
  await page.locator('#field-city').fill('Bafia').catch(() => {});
  await page.locator('#btn-submit-step-3, [data-step-next="3"]').first().click({ force: true }).catch(() => {});
  await page.waitForTimeout(600);

  // ---------- STEP 4 : professional ----------
  await page.locator('#field-activity_type').selectOption({ index: 1 }).catch(() => {});
  await page.locator('#btn-submit-step-4, [data-step-next="4"]').first().click({ force: true }).catch(() => {});
  await page.waitForTimeout(600);

  // ---------- STEP 5 : CNI ----------
  await page.locator('#field-cni_number').fill('1002938475').catch(() => {});
  const cniPath = process.env.CNI_FILE;
  if (cniPath) {
    await page.locator('#input-cni-document').setInputFiles(cniPath).catch(() => {});
  }
  await page.locator('#btn-submit-step-5, [data-step-next="5"]').first().click({ force: true }).catch(() => {});
  await page.waitForTimeout(600);

  // ---------- STEP 6 : security ----------
  await page.locator('#field-email').fill('nyobempayfowen@gmail.com').catch(() => {});
  await page.locator('#field-password').fill('Password123!').catch(() => {});
  await page.locator('#field-password_confirmation').fill('Password123!').catch(() => {});
  await page.locator('#field-accepted_terms').check({ force: true }).catch(() => {});
  await page.locator('#btn-submit-step-6').click({ force: true }).catch(() => {});
  await page.waitForTimeout(900);

  // ---------- STEP 7 : recap ----------
  const recapEmail = await page.locator('#recap-email').innerText().catch(() => '(missing)');
  const visiblePanels = await page.evaluate(() =>
    [...document.querySelectorAll('.step-panel')].filter(p => !p.classList.contains('hidden')).map(p => p.id)
  );
  const alertText = await page.locator('#register-alert, .alert, [role=alert]').first().innerText().catch(() => '');

  log.push('recap email displayed: ' + recapEmail);
  log.push('visible panels at step7: ' + JSON.stringify(visiblePanels));
  log.push('alert at step7: ' + alertText);

  // Click "Valider et recevoir mon code"
  const btn7 = page.locator('#btn-submit-step-7');
  await btn7.click({ force: true }).catch(() => {});
  await page.waitForTimeout(1200);

  const alertAfter = await page.locator('#register-alert, .alert, [role=alert]').first().innerText().catch(() => '');
  const visibleAfter = await page.evaluate(() =>
    [...document.querySelectorAll('.step-panel')].filter(p => !p.classList.contains('hidden')).map(p => p.id)
  );
  const devCode = await page.locator('#otp-dev-code').innerText().catch(() => '');

  return {
    recapEmail,
    alertAtStep7: alertText,
    alertAfterSendOtp: alertAfter,
    visiblePanelsAfterSendOtp: visibleAfter,
    devCode,
    log,
    consoleErrors: errors.slice(0, 10),
  };
}
