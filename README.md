# Dog-ear Books with Cargo: a Wishlist demo

The [Dog-ear Books](https://github.com/1994limited/wishlist-statamic-demo) demo, with the books for sale. It runs [Wishlist for Statamic](https://github.com/1994limited/wishlist-statamic) alongside [Cargo](https://builtwithcargo.dev), so you can see how the two work together, and copy from it.

It shows everything the plain demo does (saving with a ribbon, notes, several lists, sharing, guests and accounts), plus:

- **Editions.** Four books come in Hardback and Paperback (Cargo variants). The book page has one edition choice for both the basket and the list, and each edition is saved as its own item.
- **Prices and stock on the list.** Each saved book shows its price, its edition, and "Sold out" when it is.
- **Price drops.** Wishlist keeps the price a book had when it was saved. Lower a price in the Control Panel and the list says "Down £2.00 since you saved it".
- **Add to basket**, for one book, or the whole list at once with `{{ wishlist:add_to_cart }}`. Sold-out books, and books saved from the shelf without choosing an edition, stay on the list.
- **Buying from a shared list.** Whoever opens a share link can put the books straight into their own basket, in the edition that was saved.
- **Cargo's checkout**, with free shipping and Cargo's dummy payment gateway, so nothing is charged.

The book covers are drawn in CSS: there are no image files, and every title and author is invented.

## Running it

You need PHP 8.3+, Composer, and a local web server such as [Herd](https://herd.laravel.com) or `php artisan serve`.

```bash
git clone https://github.com/1994limited/wishlist-cargo-demo.git
cd wishlist-cargo-demo
composer install
cp .env.example .env
php artisan key:generate
php artisan demo:users
php artisan serve
```

Then open http://localhost:8000.

`demo:users` makes two accounts, both with the password `password`:

| Account | For |
| --- | --- |
| `jo@example.com` | Shopping: the sign-in page is filled in with it |
| `admin@example.com` | The Control Panel, at `/cp`, where the books' prices, editions and stock are |

It only runs with `APP_ENV=local`. Don't put this demo on a public server as it is: the sign-in page gives the password away.

Wishlist and Cargo are commercial addons. Running them locally like this needs no licence. The demo turns on Statamic Pro (`STATAMIC_PRO_ENABLED=true`), which Cargo needs, and which runs in Statamic's trial mode locally.

## Things to try

1. Open **Coral Arithmetic**. The hardback is sold out, so the paperback is chosen. Save it, then add it to the basket.
2. Tap the ribbon on **A Field Guide to Leaving** on the shelves. It's saved without an edition, so the list asks you to choose one.
3. Save **The Lantern Ledger**, which is sold out. The list says so, and leaves it out of "Add this list to my basket".
4. Lower a saved book's price in the Control Panel, then look at your list.
5. Copy your list's share link, open it in a private window, and add a book to that basket.

## Where to look

| File | What it shows |
| --- | --- |
| `resources/views/products/show.antlers.html` | Cargo's `{{ cart:add }}` with an edition choice, and `{{ wishlist:add }}` saving the same edition |
| `resources/views/partials/saved.antlers.html` | Each saved book's `{{ cargo:* }}` values: price, edition, stock, price drop, and a per-book "Add to basket" |
| `resources/views/wishlist.antlers.html` | `{{ wishlist:add_to_cart }}`, the whole list to the basket |
| `resources/views/wishlist/shared.antlers.html` | Buying from a shared list |
| `resources/views/partials/price.antlers.html` | A book's price, or each edition's |
| `resources/views/layout.antlers.html` | The basket count beside the saved count |
| `resources/blueprints/collections/products/product.yaml` | The book blueprint: price, stock and editions (Cargo's `money` and `product_variants` fields) |
| `resources/views/checkout/` | Cargo's prebuilt checkout, published |

Wishlist's [Cargo docs](https://github.com/1994limited/wishlist-statamic/blob/main/docs/cargo.md) explain each of these.

## Trying the drivers

Lists are kept in flat files (`storage/wishlists`) by default. To try the others, change `WISHLIST_DRIVER` in `.env`:

- `database`: then run `php artisan migrate`.
- `browser`: lists live only in your browser's localStorage, and share links carry the items. "Add this list to my basket" still works: the form sends the list along.

Nothing in the templates changes between drivers.

## Licence

The demo's own code (templates, styles, content) is MIT licensed: copy whatever helps. Wishlist and Cargo are commercial software: see [Wishlist's licence](https://github.com/1994limited/wishlist-statamic/blob/main/LICENSE.md) and [Cargo's](https://builtwithcargo.dev).
