import { test, expect } from '@playwright/test';
import { configureSimpleProduct, orderProduct, setSettings } from './utils';

test.describe('Order', () => {
  test.afterEach(async ({ page }) => {
    await page.goto('/wp-admin/edit.php?post_type=shop_order');
    const selectAll = await page.locator('#cb-select-all-1');
    if (await selectAll.isVisible()) {
      await selectAll.check();
      await page.locator('#bulk-action-selector-top').selectOption('trash');
      await page.locator('#doaction').click();
    }
  });

  test('will purchase the preset copies amount when use_preset_copies is true', async ({ page }) => {
    await setSettings(page, {
      apikey: 'test_key_12345',
      env: 'stg',
      usePresetCopies: true,
    });

    await configureSimpleProduct(page, '14');

    await orderProduct(page, 'custom-flyers');

    await page.goto('/wp-admin/edit.php?post_type=shop_order');

    await page.locator('table.wp-list-table tbody tr:first-child a.order-view').click();

    await expect(page.getByTestId('pdc-purchase-orderitem-1')).toBeEnabled();
    await page.getByTestId('pdc-purchase-orderitem-1').click();
    await page.waitForResponse('**/purchase');

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

    await page.locator('table.wp-list-table tbody tr:first-child a.order-view').click();

    await expect(page.getByTestId('pdc-purchase-orderitem-1')).toBeEnabled();
    await page.getByTestId('pdc-purchase-orderitem-1').click();
    await page.waitForResponse('**/purchase');

    await expect(page.getByTestId('pdc-ordered-copies')).toHaveText('Copies 1');
  });
});
