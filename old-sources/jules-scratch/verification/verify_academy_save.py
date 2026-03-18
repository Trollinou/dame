from playwright.sync_api import sync_playwright, expect
import time

def run_verification(page):
    # 1. Log in to WordPress admin
    page.goto("http://localhost:8000/wp-login.php")
    page.fill('input[name="log"]', "admin")
    page.fill('input[name="pwd"]', "password")
    page.click('input[name="wp-submit"]')
    expect(page).to_have_url("http://localhost:8000/wp-admin/")

    # 2. Navigate to the "Add New Adherent" page
    page.goto("http://localhost:8000/wp-admin/post-new.php?post_type=adherent")

    # 3. Fill in required fields
    page.fill('input[name="dame_first_name"]', "Test")
    page.fill('input[name="dame_last_name"]', "Adherent")
    page.fill('input[name="dame_birth_date"]', "2000-01-01")
    page.select_option('select[name="dame_license_type"]', "A")

    # 4. Select a school academy
    academy_selector = 'select[name="dame_school_academy"]'
    selected_academy_value = "Besançon"
    page.select_option(academy_selector, selected_academy_value)

    # 5. Save the post
    page.click('input[name="publish"]')

    # Wait for the "Post published" message
    expect(page.locator("#message.updated")).to_be_visible()

    # 6. Reload the page to confirm the value is saved
    page.reload()

    # 7. Verify the value of the academy dropdown
    expect(page.locator(academy_selector)).to_have_value(selected_academy_value)

    # 8. Take a screenshot
    screenshot_path = "jules-scratch/verification/academy-field-saved.png"
    page.screenshot(path=screenshot_path)
    print(f"Screenshot saved to {screenshot_path}")

with sync_playwright() as p:
    browser = p.chromium.launch()
    page = browser.new_page()
    run_verification(page)
    browser.close()
