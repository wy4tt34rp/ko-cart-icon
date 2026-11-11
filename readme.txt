=== vela: Cart Icon ===
Author: Kevin ONeill
Tags: dex, divi, cart, shortcode, icons
Requires at least: 6.0
Requires PHP: 7.4
Tested up to: 6.8
Stable tag: 1.0.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds a Divi-friendly DEX part cart icon shortcode with an overlayed product count badge.

== Description ==

vela: Cart Icon provides the `[ko_cart_icon]` shortcode for use in Divi layouts, Code Modules, headers, or shortcode-aware theme areas.

The shortcode reads the legacy DEX part cart count from `KO_Page_Part_Cart::item_count()`, `ko_get_part_cart()`, or the serialized `ko_part_cart` cookie fallback. When the cart has products, it shows the count over the cart icon. When the cart is empty, only the cart icon is shown and the icon uses the configured empty-state color.

The settings screen under Settings > vela: Cart Icon follows the KO Enterprise API Connector admin style and allows admins to change:

* Empty cart icon color
* Added cart icon color
* Badge text color
* Badge font size
* Badge background color, including transparent mode
* Badge border color and thickness
* Icon size
* DEX SVG Cart, Media Library Image, Divi ETModules, or Font Awesome icon rendering

== Shortcodes ==

Default:

`[ko_cart_icon]`

Use the built-in DEX SVG cart explicitly:

`[ko_cart_icon icon_library="dexsvg"]`

Use Font Awesome instead of the DEX SVG cart:

`[ko_cart_icon icon_library="fontawesome"]`

Use a Media Library image URL:

`[ko_cart_icon icon_library="media" media_icon_url="/wp-content/uploads/cart.svg"]`

Override icon size for one placement:

`[ko_cart_icon size="32" class="header-cart"]`

Use a custom cart URL or accessible label:

`[ko_cart_icon url="/cart/" label="Open cart"]`

Optional shortcode attributes:

* `icon_library` - `dexsvg`, `media`, `etmodules`, or `fontawesome`
* `builtin_icon` - built-in SVG icon key
* `media_icon_id` - Media Library attachment ID
* `media_icon_url` - Media Library image URL
* `etmodules_icon` - Divi ETModules hexadecimal glyph code
* `fontawesome_icon` - Font Awesome class list
* `empty_color` - hex color for empty cart state
* `active_color` - hex color for cart-with-products state
* `text_color` - hex color for badge text
* `badge_font_size` - badge number font size in pixels, or `0` for automatic sizing
* `badge_background_color` or `badge_bg_color` - hex color for badge background, or `transparent`
* `badge_background_transparent` - `1` to force transparent badge background
* `badge_border_color` - hex color for badge border
* `badge_border_width` - badge border thickness in pixels
* `size` or `icon_size` - icon size in pixels
* `url` - custom cart URL
* `label` - accessible link label
* `class` - extra CSS class list

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or upload the ZIP in Plugins > Add New.
2. Activate vela: Cart Icon.
3. Go to Settings > vela: Cart Icon.
4. Place `[ko_cart_icon]` in a Divi Code Module or shortcode-aware area.

== Changelog ==

= 1.0.6 =
* Added badge font size setting and shortcode override.
* Fixed admin notification text contrast so saved/reset notices are readable in the hero area.

= 1.0.5 =
* Moved the count badge into the middle of the cart basket.
* Added badge background color with transparent mode.
* Added badge border color and thickness settings.
* Kept the badge round at smaller icon sizes.

= 1.0.4 =
* Added conditional icon selection fields below the Icon Library dropdown.
* Added Media Library image selection and masked rendering so selected images can follow the configured cart colors.
* Hardened DEX cart counting for nested cart row data and fallback sources.
* Removed hover color/background changes from the frontend cart icon.

= 1.0.3 =
* Added a built-in DEX SVG cart icon based on `/wp-content/uploads/2024/08/shopping-cart-76be43.svg`.
* Made the DEX SVG cart the default icon style for new installs and resets.
* Migrated the old default Font Awesome setting to the DEX SVG cart during the 1.0.3 upgrade.
* Updated the admin preview to switch between DEX SVG, ETModules, and Font Awesome correctly.

= 1.0.2 =
* Made the Font Awesome cart option self-rendering with an embedded SVG mask.
* Tuned the empty cart icon spacing to match a simple standalone cart mark.
* Made Font Awesome the default icon style for new installs.

= 1.0.1 =
* Removed incorrect third-party cart assumptions and dependency metadata.
* Switched cart count handling to the DEX part cart contract.
* Added DEX add-to-cart callback support and AJAX count refresh.

= 1.0.0 =
* Initial release.
