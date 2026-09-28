export default async function run(page, ui) {
  const results = [];

  // Step 1 -> producer
  await page.locator('input[type=radio][value=producer]').first()
    .check({ force: true }).catch(async () => {
      await page.locator('#btn-pick-producer, [data-role=producer]').first().click({ force: true }).catch(() => {});
    });
  await page.locator('#btn-submit-step-1, [data-step-next="1"]').first().click({ force: true }).catch(() => {});
  await page.waitForTimeout(300);

  // Step 2
  await page.fill('#field-first_name', 'Jean').catch(() => {});
  await page.fill('#field-last_name', 'Ngono').catch(() => {});
  const g = page.locator('#field-gender');
  if (await g.count()) {
    const tag = await g.evaluate(el => el.tagName);
    if (tag === 'SELECT') await g.selectOption({ index: 1 }).catch(() => {});
    else await g.first().check({ force: true }).catch(() => {});
  }
  await page.fill('#field-date_of_birth', '1990-05-10').catch(() => {});
  await page.fill('#field-phone', '655112233').catch(() => {});
  await page.locator('#btn-submit-step-2, [data-step-next="2"]').first().click({ force: true }).catch(() => {});
  await page.waitForTimeout(700);

  // DELIBERATE: jump straight to the recap via the "Modifier" buttons / step 6 submit
  // but WITHOUT filling email -> reproduce the reported SOURCE of the message.
  // First, check which panel is visible and whether the recap email is empty.
  const panelsBefore = await page.evaluate(() =>
    [...document.querySelectorAll('.step-panel')].filter(p => !p.classList.contains('hidden')).map(p => p.id)
  );
  results.push({ where: 'after step2 submit', panelsBefore });

  // Try to reach the recap by clicking recap-email after an EMPTY email submit.
  await page.locator('#field-email').fill('').catch(() => {});
  await page.locator('#btn-submit-step-6').click({ force: true }).catch(() => {});
  await page.waitForTimeout(700);

  const errEmail = await page.locator('#field-err-email').innerText().catch(() => '');
  const errTerms = await page.locator('#field-err-accepted_terms').innerText().catch(() => '');
  const panelsAfterEmptyEmail = await page.evaluate(() =>
    [...document.querySelectorAll('.step-panel')].filter(p => !p.classList.contains('hidden')).map(p => p.id)
  );
  results.push({ where: 'empty email submit', errEmail, errTerms, panelsAfterEmptyEmail });

  // Now fill a VALID email but skip the terms checkbox.
  await page.locator('#field-email').fill('nyobempayfowen@gmail.com').catch(() => {});
  await page.locator('#field-password').fill('Password123!').catch(() => {});
  await page.locator('#field-password_confirmation').fill('Password123!').catch(() => {});
  await page.locator('#btn-submit-step-6').click({ force: true }).catch(() => {});
  await page.waitForTimeout(700);

  const errTerms2 = await page.locator('#field-err-accepted_terms').innerText().catch(() => '');
  const panelsNoTerms = await page.evaluate(() =>
    [...document.querySelectorAll('.step-panel')].filter(p => !p.classList.contains('hidden')).map(p => p.id)
  );
  results.push({ where: 'valid email, terms unchecked', errTerms2, panelsNoTerms });

  return { results };
}
