# vela: Cart Icon

Small WordPress plugin that adds a Divi-friendly DEX part cart icon shortcode with an overlayed product count badge.

## Shortcode

```text
[ko_cart_icon]
```

Optional overrides:

```text
[ko_cart_icon icon_library="dexsvg"]
[ko_cart_icon icon_library="media" media_icon_url="/wp-content/uploads/cart.svg"]
[ko_cart_icon icon_library="fontawesome"]
[ko_cart_icon size="32" class="header-cart"]
[ko_cart_icon badge_font_size="11"]
[ko_cart_icon badge_background_color="transparent" badge_border_color="#0f6b37"]
[ko_cart_icon url="/cart/" label="Open cart"]
```

The admin page is available under `Settings > vela: Cart Icon` and follows the KO Enterprise API Connector admin screen style.

## Settings

- Empty cart icon color
- Added cart icon color
- Badge text color
- Badge font size
- Badge background color, including transparent mode
- Badge border color and thickness
- Icon size
- Icon library: DEX SVG Cart, Media Library Image, Divi ETModules, or Font Awesome
- Conditional icon fields appear after the icon library selection.
- DEX SVG Cart is the default and is based on `/wp-content/uploads/2024/08/shopping-cart-76be43.svg`.
- Media Library images are rendered as masks so the empty and added cart colors still apply.
- Font Awesome is self-rendered with an embedded SVG mask so it does not depend on a separately loaded icon font.

## Notes

The shortcode uses the DEX part cart count from `KO_Page_Part_Cart::item_count()`, `ko_get_part_cart()`, or the serialized `ko_part_cart` cookie fallback. Empty carts render as a gray icon with no badge. Carts with products render the configured active color and show a round count badge inside the cart basket. Hover keeps the current state color and transparent background.
