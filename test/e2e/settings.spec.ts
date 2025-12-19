import { test, expect } from '@playwright/test';

test.describe('Settings Page', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=pdc-pod');
  });
  test.afterAll(async ({ browser }) => {
    const afterAllPage = await browser.newPage();
    await afterAllPage.goto('/wp-admin/admin.php?page=pdc-pod');
    await afterAllPage.getByTestId('pdc-pod-apikey').fill('test_key_12345');
  });

  test.describe('general settings', () => {
    test('when settings page is loaded for the first time, environment is staging', async ({ page }) => {
      // Environment is on test
      await expect(page.getByTestId('pdc-pod-environment')).toHaveValue('stg');

      // Link is going to app.stg.print.com/account
      await expect(page.getByTestId('pdc-pod-environment-link')).toHaveAttribute('href', 'https://app.stg.print.com/account');
    });

    test('user can enter a valid API key and save it', async ({ page }) => {
      // enter key 'test_key_12345'
      await page.getByTestId('pdc-pod-apikey').fill('test_key_12345');

      // save it
      await page.getByRole('button', { name: 'Save Settings' }).click();

      // assert that it is still there
      await expect(page.getByTestId('pdc-pod-apikey')).toHaveValue('test_key_12345');
    });

    test('show notification when environment or key has changed but not saved when verifying', async ({ page }) => {
      // enter just any key
      await page.getByTestId('pdc-pod-apikey').fill('unsaved_key');

      // expect a dialog
      page.on('dialog', async (dialog) => {
        await expect(dialog.message()).toBe('Please save the settings before verifying the API key');
        await dialog.accept();
      });

      // attempt to verify it
      await page.getByTestId('pdc-pod-verify-key').click();
    });

    test('show error when api key is invalid', async ({ page }) => {

      // set incorrect key
      await page.getByTestId('pdc-pod-apikey').fill('invalid_key');

      // save it
      await page.getByRole('button', { name: 'Save Settings' }).click();

      // verify it
      await page.getByTestId('pdc-pod-verify-key').click();

      // assert
      await expect(page.getByText('API Key is not valid. Check your environment and API Key')).toBeVisible();
    });

    test('when environment is set to live, show link to production environment', async ({ page }) => {

      // select prod
      await page.getByTestId('pdc-pod-environment').selectOption('prod');
      await page.getByTestId('pdc-pod-apikey').fill('test_key_12345');

      // save
      await page.getByRole('button', { name: 'Save Settings' }).click();

      // assert
      await expect(page.getByTestId('pdc-pod-environment-link')).toHaveAttribute('href', 'https://app.print.com/account');

      // verify to see if verification request goes to api.print.com and not stg
      await page.getByTestId('pdc-pod-verify-key').click();

      // assert
      await expect(page.getByText('API Key verified. You are now connected!')).toBeVisible();

      // cleanup
      await page.getByTestId('pdc-pod-environment').selectOption('stg');
      await page.getByRole('button', { name: 'Save Settings' }).click();
    });
  });

  test.describe('product settings', () => {
    test('user can check the preset copies checkbox and save it', async ({ page }) => {
      // Check the checkbox
      await page.getByTestId('pdc-pod-use_preset_copies').check();

      // Verify it's checked
      await expect(page.getByTestId('pdc-pod-use_preset_copies')).toBeChecked();

      // Save settings
      await page.getByRole('button', { name: 'Save Settings' }).click();

      // Reload page to verify persistence
      await page.reload();
      await expect(page.getByTestId('pdc-pod-use_preset_copies')).toBeChecked();
    });

    test('user can uncheck the preset copies checkbox and save it', async ({ page }) => {
      // First, ensure the checkbox is checked
      await page.getByTestId('pdc-pod-use_preset_copies').check();
      await page.getByRole('button', { name: 'Save Settings' }).click();
      await expect(page.getByTestId('pdc-pod-use_preset_copies')).toBeChecked();

      // Now uncheck it
      await page.getByTestId('pdc-pod-use_preset_copies').uncheck();
      await expect(page.getByTestId('pdc-pod-use_preset_copies')).not.toBeChecked();

      // Save settings
      await page.getByRole('button', { name: 'Save Settings' }).click();

      // Reload page to verify persistence
      await page.reload();
      await expect(page.getByTestId('pdc-pod-use_preset_copies')).not.toBeChecked();
    });

    test('checkbox state persists across page reloads', async ({ page }) => {
      // Check the checkbox and save
      await page.getByTestId('pdc-pod-use_preset_copies').check();
      await page.getByRole('button', { name: 'Save Settings' }).click();

      // Navigate away and back
      await page.goto('/wp-admin/admin.php?page=pdc-pod');

      // Verify checkbox is still checked
      await expect(page.getByTestId('pdc-pod-use_preset_copies')).toBeChecked();
    });

    test('both general and product settings can be saved together', async ({ page }) => {
      // Set general settings
      await page.getByTestId('pdc-pod-apikey').fill('combined_test_key');
      await page.getByTestId('pdc-pod-environment').selectOption('stg');

      // Set product settings
      await page.getByTestId('pdc-pod-use_preset_copies').check();

      // Save all settings
      await page.getByRole('button', { name: 'Save Settings' }).click();

      // Verify both sections are saved correctly
      await expect(page.getByTestId('pdc-pod-apikey')).toHaveValue('combined_test_key');
      await expect(page.getByTestId('pdc-pod-environment')).toHaveValue('stg');
      await expect(page.getByTestId('pdc-pod-use_preset_copies')).toBeChecked();

      // Reload to confirm persistence
      await page.reload();
      await expect(page.getByTestId('pdc-pod-apikey')).toHaveValue('combined_test_key');
      await expect(page.getByTestId('pdc-pod-environment')).toHaveValue('stg');
      await expect(page.getByTestId('pdc-pod-use_preset_copies')).toBeChecked();

      // reset key
      await page.getByTestId('pdc-pod-apikey').fill('test_key_12345');
    });
  });
});
