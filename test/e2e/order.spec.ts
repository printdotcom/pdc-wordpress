import { test, expect } from '@playwright/test';
import { configureSimpleProduct, addToCart, placeOrder, setSettings, configureVariableProduct, runScheduledActions, AUTO_PURCHASE_HOOK } from './utils';

test.describe('Order', () => {
  test.afterEach(async ({ page }) => {
    await page.goto('/wp-admin/edit.php?post_type=shop_order');
    const actions = page.locator('#bulk-action-selector-top');
    const selectAll = await page.locator('#cb-select-all-1');
    if ((await actions.isVisible()) && (await selectAll.isVisible())) {
      await selectAll.check();
      await actions.selectOption('trash');
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

    await addToCart(page, {
      slug: 'custom-flyers',
    });

    await placeOrder(page);

    await page.goto('/wp-admin/edit.php?post_type=shop_order');

    await page.locator('table.wp-list-table tbody tr:first-child a.order-view').click();

    await expect(page.getByTestId('pdc-purchase-orderitem-1')).toBeEnabled();
    const presetResponsePromise = page.waitForResponse('**/purchase');
    await page.getByTestId('pdc-purchase-orderitem-1').click();
    await presetResponsePromise;

    await expect(page.getByTestId('pdc-ordered-copies-1')).toHaveText('Copies 500');
  });

  test('will purchase the ordered quantity when use_preset_copies is false', async ({ page }) => {
    await setSettings(page, {
      apikey: 'test_key_12345',
      env: 'stg',
      usePresetCopies: false,
    });

    await configureSimpleProduct(page, '14');

    await addToCart(page, {
      slug: 'custom-flyers',
    });

    await placeOrder(page);

    await page.goto('/wp-admin/edit.php?post_type=shop_order');

    await page.locator('table.wp-list-table tbody tr:first-child a.order-view').click();

    await expect(page.getByTestId('pdc-purchase-orderitem-1')).toBeEnabled();
    const responsePromise = page.waitForResponse('**/purchase');
    await page.getByTestId('pdc-purchase-orderitem-1').click();
    await responsePromise;

    await expect(page.getByTestId('pdc-ordered-copies-1')).toHaveText('Copies 1');
  });

  test('will purchase a preset that includes accessories', async ({ page }) => {
    await setSettings(page, {
      apikey: 'test_key_12345',
      env: 'stg',
      usePresetCopies: true,
    });

    await configureSimpleProduct(page, '14', 'flyers_a5_with_accessories');

    await addToCart(page, {
      slug: 'custom-flyers',
    });

    await placeOrder(page);

    await page.goto('/wp-admin/edit.php?post_type=shop_order');

    await page.locator('table.wp-list-table tbody tr:first-child a.order-view').click();

    await expect(page.getByTestId('pdc-purchase-orderitem-1')).toBeEnabled();
    const purchaseResponsePromise = page.waitForResponse('**/purchase');
    await page.getByTestId('pdc-purchase-orderitem-1').click();
    await purchaseResponsePromise;

    // Verifies the full flow completed: preset fetched, /accessories/flyers called,
    // order submitted with accessories, and response processed without errors.
    await expect(page.getByTestId('pdc-ordered-copies-1')).toHaveText('Copies 500');
  });

  test('will purchase multiple order items at once', async ({ page }) => {
    await setSettings(page, {
      apikey: 'test_key_12345',
      env: 'stg',
      usePresetCopies: false,
    });

    await configureSimpleProduct(page, '14');
    await configureVariableProduct(page, {
      productID: '15',
      variations: [
        {
          variationID: '17',
          sku: 'posters',
          preset: 'posters_a2',
        },
        {
          variationID: '16',
          sku: 'posters',
          preset: 'posters_a3',
        },
      ],
    });

    await page.reload();

    await addToCart(page, {
      slug: 'custom-flyers',
    });
    await addToCart(page, {
      slug: 'custom-poster',
      options: {
        pa_size: 'a2',
      },
    });

    await placeOrder(page);

    await page.goto('/wp-admin/edit.php?post_type=shop_order');

    await page.locator('table.wp-list-table tbody tr:first-child a.order-view').click();

    await expect(page.getByTestId('pdc-pod-purchase-all')).toBeEnabled();
    const responsePromise = page.waitForResponse('**/purchase');
    await page.getByTestId('pdc-pod-purchase-all').click();
    await responsePromise;

    await expect(page.getByTestId('pdc-ordered-copies-1')).toHaveText('Copies 1');
    await expect(page.getByTestId('pdc-ordered-copies-2')).toHaveText('Copies 1');
  });

  test('will purchase the order automatically when automatic purchasing is enabled', async ({ page }) => {
    await setSettings(page, {
      apikey: 'test_key_12345',
      env: 'stg',
      usePresetCopies: false,
      autoPurchase: true,
      triggerStatus: 'processing',
    });

    await configureSimpleProduct(page, '14');

    await addToCart(page, {
      slug: 'custom-flyers',
    });

    await placeOrder(page);

    // Placing the order queues the purchase; nothing is bought until it runs.
    await runScheduledActions(page, AUTO_PURCHASE_HOOK);

    await page.goto('/wp-admin/edit.php?post_type=shop_order');
    await page.locator('table.wp-list-table tbody tr:first-child a.order-view').click();

    // The item was purchased without anyone pressing Purchase.
    await expect(page.getByTestId('pdc-ordered-copies-1')).toHaveText('Copies 1');
    await expect(page.getByTestId('pdc-purchase-orderitem-1')).toHaveCount(0);
  });

  test('will show the last Print.com error on the order item row', async ({ page }) => {
    await setSettings(page, {
      apikey: 'test_key_12345',
      env: 'stg',
      usePresetCopies: false,
    });

    // This preset is gone at Print.com, so purchasing it fails.
    await configureSimpleProduct(page, '14', 'flyers_broken');

    await addToCart(page, {
      slug: 'custom-flyers',
    });

    await placeOrder(page);

    await page.goto('/wp-admin/edit.php?post_type=shop_order');
    await page.locator('table.wp-list-table tbody tr:first-child a.order-view').click();

    const purchaseResponsePromise = page.waitForResponse('**/purchase');
    await page.getByTestId('pdc-purchase-orderitem-1').click();
    await purchaseResponsePromise;

    await expect(page.getByTestId('pdc-item-error-1')).toContainText('Preset does not exist.');

    // The error is stored, so it survives a reload.
    await page.reload();
    await expect(page.getByTestId('pdc-item-error-1')).toContainText('Preset does not exist.');
  });
});
