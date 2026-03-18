from playwright.sync_api import sync_playwright, expect

def run_verification(page):
    # Set up a listener for console errors
    errors = []
    page.on("console", lambda msg: errors.append(msg.text) if msg.type == "error" else None)

    # 1. Log in to WordPress admin
    page.goto("http://localhost:8000/wp-login.php")
    page.fill('input[name="log"]', "admin")
    page.fill('input[name="pwd"]', "password")
    page.click('input[name="wp-submit"]')

    # Wait for navigation to the dashboard after login
    expect(page).to_have_url("http://localhost:8000/wp-admin/")

    # 2. Navigate to the "Add New Adherent" page
    page.goto("http://localhost:8000/wp-admin/post-new.php?post_type=adherent")

    # 3. Check for the presence of a key element that depends on the scripts
    # The address field with autocompletion is a good target
    address_input = page.locator("#dame_address_1")
    expect(address_input).to_be_visible()

    # 4. Take a screenshot
    screenshot_path = "jules-scratch/verification/adherent-editor-no-errors.png"
    page.screenshot(path=screenshot_path)
    print(f"Screenshot saved to {screenshot_path}")

    # 5. Check for 404 errors in the console log
    for error in errors:
        if "404" in error or "Failed to load resource" in error:
            raise Exception(f"Detected a 404 error in the console: {error}")

with sync_playwright() as p:
    browser = p.chromium.launch()
    page = browser.new_page()
    run_verification(page)
    browser.close()
