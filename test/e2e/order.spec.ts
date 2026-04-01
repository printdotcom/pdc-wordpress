import { test, expect } from '@playwright/test';
import { configureSimpleProduct, orderProduct, setSettings } from './utils';

test.describe('Order', () => {
  test('will purchase the preset copies amount when use_preset_copies is true', async ({ page }) => {
    await setSettings(page, {
      apikey: 'test_key_12345',
      env: 'stg',
      usePresetCopies: true,
    });

    await configureSimpleProduct(page, '14');

    await orderProduct(page, 'custom-flyers');

    await page.goto('/wp-admin/edit.php?post_type=shop_order');

    // view latest order
    await page.locator('table.wp-list-table tbody tr:first-child a.order-view').click();

    const responsePromise = page.waitForResponse((r) => r.ok() && r.url().includes('/purchase'));
    await page.getByTestId('pdc-purchase-orderitem-1').click();
    await responsePromise;

    // We have configured a preset with 300 copies (see preset.flyers_a5.json), so should be 500 copies.
    await expect(page.getByTestId('pdc-ordered-copies')).toHaveText('Copies 500');
  });

  test('will purchase the ordered quantity when use_preset_copies is false', async ({ page }) => {
    await setSettings(page, {
      apikey: 'test_key_12345',
      env: 'stg',
      usePresetCopies: false,
    });

    await configureSimpleProduct(page, '14');

    await orderProduct(page, 'custom-flyers');

    await page.goto('/wp-admin/edit.php?post_type=shop_order');

    const responsePromise = page.waitForResponse((r) => r.ok() && r.url().includes('/purchase'));
    await page.getByTestId('pdc-purchase-orderitem-1').click();
    await responsePromise;

    await expect(page.getByTestId('pdc-ordered-copies')).toHaveText('Copies 1');
  });
});
