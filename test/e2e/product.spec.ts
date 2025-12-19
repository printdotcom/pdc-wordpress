import path from 'path';
import { test, expect } from '@playwright/test';
import { configureSimpleProduct, setSettings } from './utils';

test.describe('product', () => {
  test('can configure a preset for a simple product', async ({ page }) => {
    await setSettings(page, {
      apikey: 'test_key_12345',
      env: 'stg',
      usePresetCopies: false,
    });

    await configureSimpleProduct(page, '14');
    await page.getByRole('link', { name: 'Print.com' }).click();
    await expect(page.getByTestId('pdc-preset-id')).toHaveValue('flyers_a5');
  });

  test('can configure a PDF for a product', async ({ page }) => {
    await setSettings(page, {
      apikey: 'test_key_12345',
      env: 'stg',
      usePresetCopies: false,
    });

    await page.goto('/wp-admin/post.php?post=14&action=edit');
    await page.getByRole('link', { name: 'Print.com' }).click();

    // select product
    await page.getByTestId('pdc-product-sku').selectOption('flyers');

    await page.getByRole('link', { name: 'Choose file' }).click();
    await page.getByRole('tab', { name: 'Upload files' }).click();

    const fileChooserPromise = page.waitForEvent('filechooser');
    await page.getByRole('button', { name: 'Select Files' }).click();
    const fileChooser = await fileChooserPromise;
    await fileChooser.setFiles(path.join(__dirname, `/fixtures/pdc_flyera5.pdf`));

    await page.getByRole('button', { name: 'Select File', exact: true }).click();

    await page.getByRole('button', { name: 'Update' }).click();

    await page.getByRole('link', { name: 'Print.com' }).click();

    const input = page.getByTestId('pdc-file-upload');
    await expect(input).toHaveValue(/pdc_flyera5/);
  });

  test('can configure a preset and file for a variable product', async ({ page }) => {
    await setSettings(page, {
      apikey: 'test_key_12345',
      env: 'stg',
      usePresetCopies: false,
    });

    await page.goto('/wp-admin/post.php?post=15&action=edit');

    // go to pdc tab
    await page.getByRole('link', { name: 'Print.com' }).click();

    // select product
    await page.getByTestId('pdc-product-sku').selectOption('posters');

    // save and wait for presets to appear
    await Promise.all([
      page.getByRole('button', { name: 'Update' }).click(),
      page.waitForResponse((r) => r.ok() && r.url().includes('rest_route=/pdc/v1/products/posters/presets'))]);

    await page.waitForLoadState('networkidle');

    await page.locator('a[href="#variable_product_options"]').click();

    // open A2
    const tableRow = page
      .locator('.woocommerce_variation.wc-metabox')
      .filter({ has: page.locator('a.remove_variation[rel="17"]') })
      .first();

    await tableRow.locator('a.edit_variation.edit').click();
    const panel = tableRow.locator('.woocommerce_variable_attributes.wc-metabox-content').first();

    await expect(panel.getByTestId('variation_preset_17')).toBeVisible();

    // select preset
    await page.getByTestId('variation_preset_17').selectOption('posters_a2');

    // save variation
    await page.getByRole('button', { name: 'Save changes' }).click();
  });
});
