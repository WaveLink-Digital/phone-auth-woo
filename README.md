# Phone Authentication for WooCommerce 📱

A WordPress plugin that adds phone number authentication and registration functionality to WooCommerce My Account page.

## Features ✨

- Phone number verification via OTP
- Secure SMS integration with iSend API
- Rate limiting for verification attempts
- Seamless integration with WooCommerce login/register forms
- Mobile-friendly UI
- Configurable settings

## Installation 🚀

1. Upload the `phone-auth-woo` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to WooCommerce > Phone Authentication to configure your iSend API settings

## Configuration ⚙️

1. Navigate to WooCommerce > Phone Authentication
2. Enter your iSend API credentials:
   - API Key
   - Sender ID (max 11 characters)
3. Save changes

## Usage 📖

The plugin automatically adds phone verification to:
- WooCommerce login form
- WooCommerce registration form
- My Account page

Users will need to:
1. Enter their phone number
2. Click "Send Verification Code"
3. Enter the 6-digit OTP received via SMS
4. Complete their login/registration

## Testing Guide 🧪

### Prerequisites
- WooCommerce installed and activated
- Valid iSend API credentials
- Test phone number

### Test Cases

1. **Settings Configuration**
   - Navigate to WooCommerce > Phone Authentication
   - Verify settings are saved correctly
   - Test with invalid API credentials

2. **Registration Flow**
   - Go to My Account > Register
   - Enter registration details including phone number
   - Verify OTP sending functionality
   - Test with:
     - Invalid phone numbers
     - Already registered numbers
     - Invalid OTP codes

3. **Login Flow**
   - Go to My Account > Login
   - Enter registered phone number
   - Verify OTP process
   - Test with:
     - Unregistered numbers
     - Wrong OTP codes

4. **Rate Limiting**
   - Try requesting multiple OTPs
   - Verify 5 attempts limit
   - Check 1-hour cooldown period

5. **Error Handling**
   - Test network failures
   - Test API timeouts
   - Verify error messages

### Common Issues

1. **OTP not received**
   - Check API credentials
   - Verify phone number format
   - Check API balance

2. **Form submission issues**
   - Clear browser cache
   - Check JavaScript console
   - Verify nonce validation

3. **Style conflicts**
   - Check theme compatibility
   - Inspect CSS specificity

## Support 💬

For support, please:
1. Check the documentation
2. Test with a default WordPress theme
3. Disable other plugins to check conflicts
4. Contact support with:
   - WordPress version
   - WooCommerce version
   - Theme details
   - Error messages/screenshots

## Security Considerations 🔒

- API credentials are stored encrypted
- Rate limiting prevents abuse
- Nonce validation for AJAX calls
- Input sanitization
- OTP expiration after 2 minutes
- Maximum 5 verification attempts per hour

## Changelog 📝

### 1.0.0
- Initial release
- Basic phone authentication
- iSend SMS integration
- Rate limiting
- WooCommerce integration