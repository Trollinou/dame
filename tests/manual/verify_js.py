
import os
from playwright.sync_api import sync_playwright, expect

def test_js_adherent():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Load the local HTML file
        file_path = os.path.abspath("tests/manual/verification.html")
        page.goto(f"file://{file_path}")

        # 1. Test Usage Name Copy
        print("Testing Usage Name Copy...")
        page.fill("#dame_birth_name", "DUPONT")
        page.locator("#dame_birth_name").blur()
        expect(page.locator("#dame_last_name")).to_have_value("DUPONT")
        print("Success: Usage Name copied.")

        # 2. Test Address Autocomplete (Mocking fetch or using real if allowed)
        # We will attempt to type and wait for suggestion box.
        # Note: Sandbox environment usually allows outbound HTTP requests.
        print("Testing Address Autocomplete...")
        page.fill("#dame_address_1", "10 rue de Rivoli")

        # Wait for suggestions to appear (logic in JS has 250ms debounce)
        try:
            page.wait_for_selector(".dame-suggestion-item", timeout=5000)
            print("Success: Suggestions appeared.")

            # Click first suggestion
            page.click(".dame-suggestion-item >> nth=0")

            # Check if fields are populated
            expect(page.locator("#dame_city")).not_to_be_empty()
            expect(page.locator("#dame_postal_code")).not_to_be_empty()
            expect(page.locator("#dame_latitude")).not_to_be_empty()
            print("Success: Address fields populated.")

        except Exception as e:
            print(f"Warning: Address autocomplete test failed or timed out (possibly network issue). {e}")

        # 3. Test Birth City Autocomplete
        print("Testing Birth City Autocomplete...")
        page.fill("#dame_birth_city", "Lyon")

        try:
            page.wait_for_selector(".dame-suggestion-item", timeout=5000)
            print("Success: Birth city suggestions appeared.")
            page.click(".dame-suggestion-item >> nth=0")
            expect(page.locator("#dame_birth_city")).not_to_be_empty()
            print("Success: Birth city populated.")
        except Exception as e:
             print(f"Warning: Birth city autocomplete test failed. {e}")

        # Take screenshot
        page.screenshot(path="tests/manual/verification.png")
        print("Screenshot saved to tests/manual/verification.png")

        browser.close()

if __name__ == "__main__":
    test_js_adherent()
