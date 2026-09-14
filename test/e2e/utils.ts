import { expect } from '@playwright/test';
import path from 'path';

interface AddToCartParam {
  slug: string;
  options?: { [key: string]: string };
}
export async function addToCart(page, params: AddToCartParam) {
  await page.goto(`/?product=${encodeURIComponent(params.slug)}`);
  if (params.options) {
    for (const [key, value] of Object.entries(params.options)) {
      await page.locator(`#${key}`).selectOption(value);
    }
  }
  const addToCartButton = page.getByRole('button', { name: 'Add to cart', exact: true });
  await expect(addToCartButton).not.toHaveClass(/disabled/);
  await addToCartButton.click();
}

export async function placeOrder(page) {
  // 12 = checkout in seeder
  await page.goto(`/?page_id=12`);
  await page.locator('#billing_first_name').fill('Test');
  await page.locator('#billing_last_name').fill('User');
  await page.getByRole('textbox', { name: 'Street address' }).fill('Teugseweg 18a');
  await page.getByRole('textbox', { name: 'Town / City' }).fill('Deventer');
  await page.locator('#billing_postcode').fill('63104');
  await page.getByRole('textbox', { name: 'Phone' }).fill('0612312312');
  await page.getByRole('button', { name: 'Place order' }).click();
  await page.waitForResponse(/\/?wc-ajax=checkout/);
}

export const AUTO_PURCHASE_HOOK = 'pdc_pod_auto_purchase_order';

interface Settings {
  apikey: string;
  env: 'prod' | 'stg';
  usePresetCopies: boolean;
  autoPurchase?: boolean;
  triggerStatus?: string;
}
export async function setSettings(page, settings: Settings) {
  await page.goto('/wp-admin/admin.php?page=pdc-pod');
  await page.getByTestId('pdc-pod-apikey').fill(settings.apikey);
  await page.getByTestId('pdc-pod-environment').selectOption('stg');
  await page.getByRole('button', { name: 'Save Settings' }).click();

  await page.goto('/wp-admin/admin.php?page=pdc-pod&tab=product');
  if (settings.usePresetCopies) {
    await page.getByTestId('pdc-pod-use_preset_copies').check();
  } else {
    await page.getByTestId('pdc-pod-use_preset_copies').uncheck();
  }
  await page.getByRole('button', { name: 'Save Settings' }).click();

  // Always visit the orders tab so automatic purchasing cannot leak between tests.
  await page.goto('/wp-admin/admin.php?page=pdc-pod&tab=orders');
  if (settings.autoPurchase) {
    await page.getByTestId('pdc-pod-auto_purchase').check();
    await page.getByTestId('pdc-pod-trigger_status').selectOption(settings.triggerStatus ?? 'processing');
  } else {
    await page.getByTestId('pdc-pod-auto_purchase').uncheck();
  }
  await page.getByRole('button', { name: 'Save Settings' }).click();
}

/**
 * Runs every pending Action Scheduler job for a hook from the WooCommerce admin.
 *
 * The queue is normally drained by WP-Cron, which is too unpredictable for a
 * test, so the jobs are executed explicitly through their row action.
 *
 * All pending jobs are drained rather than just the first: earlier tests can
 * leave jobs behind for orders their afterEach has already trashed, and running
 * one of those would report success while this test's job is still queued.
 *
 * Finding nothing pending is fine. With alternate cron enabled, a page load
 * earlier in the test may already have run the job.
 */
export async function runScheduledActions(page, hook: string) {
  const listUrl = `/wp-admin/admin.php?page=wc-status&tab=action-scheduler&status=pending&s=${encodeURIComponent(hook)}`;
  let ran = 0;

  for (let attempt = 0; attempt < 25; attempt++) {
    await page.goto(listUrl);

    const rows = page.locator('table.wp-list-table tbody tr', { hasText: hook });
    if ((await rows.count()) === 0) {
      break;
    }

    // Row actions are hidden until hover, so follow the link rather than click it.
    const runUrl = await rows.first().locator('a', { hasText: 'Run' }).first().getAttribute('href');
    expect(runUrl).toBeTruthy();

    await page.goto(runUrl);
    await expect(page.locator('#wpbody-content')).toContainText('Successfully');
    ran++;
  }

  return ran;
}

export async function configureSimpleProduct(page, productID: string, presetID: string = 'flyers_a5') {
  const productURL = `/wp-admin/post.php?post=${productID}&action=edit`;
  await page.goto(productURL);
  await page.getByRole('link', { name: 'Print.com' }).click();

  // select product
  await page.getByTestId('pdc-product-sku').selectOption('flyers');

  // loading presets for selected product
  await page.waitForResponse(/\/pdc\/v1\/products/, {
    timeout: 1000,
  });

  // select preset
  await page.getByTestId('pdc-preset-id').selectOption(presetID);

  // pdf file = fixture
  await page.getByRole('link', { name: 'Choose file' }).click();
  await page.getByRole('tab', { name: 'Upload files' }).click();
  const fileChooserPromise = page.waitForEvent('filechooser');
  await page.getByRole('button', { name: 'Select Files' }).click();
  const fileChooser = await fileChooserPromise;
  await fileChooser.setFiles(path.join(__dirname, `/fixtures/pdc_flyera5.pdf`));
  await page.getByRole('button', { name: 'Select File', exact: true }).click();

  await page.getByRole('button', { name: 'Update' }).click();
  await page.waitForURL(productURL);
}

interface ConfigureVariableProductParams {
  productID: string;
  variations: {
    variationID: string;
    sku: string;
    preset: string;
  }[];
}
export async function configureVariableProduct(page, params: ConfigureVariableProductParams) {
  const productURL = `/wp-admin/post.php?post=${params.productID}&action=edit`;
  await page.goto(productURL);
  await page.getByRole('link', { name: 'Variations' }).click();
  await page.waitForResponse('**/admin-ajax.php');

  for (let i = 0; i < params.variations.length; i++) {
    const variation = params.variations[i];
    await page.locator(`.woocommerce_variation:has-text('#${variation.variationID}')`).click();
    await page.getByTestId(`variation_sku_${variation.variationID}`).selectOption(params.variations[i].sku);

    await page.waitForResponse(/\/pdc\/v1\/products/, {
      timeout: 1000,
    });

    await page.getByTestId(`variation_preset_${variation.variationID}`).selectOption(params.variations[i].preset);
    await page.getByTestId(`variation_file_${variation.variationID}`).click();
    await page.getByRole('tab', { name: 'Upload files' }).click();
    const fileChooserPromise = page.waitForEvent('filechooser');
    await page.getByRole('button', { name: 'Select Files' }).click();
    const fileChooser = await fileChooserPromise;
    await fileChooser.setFiles(path.join(__dirname, `/fixtures/pdc_flyera5.pdf`));
    await page.getByRole('button', { name: 'Select File', exact: true }).click();
  }

  await page.getByRole('button', { name: 'Update' }).click();
  await page.waitForURL(productURL);
}
