# Dog-ear Books: a Reviews demo

A made-up bookshop built with [Reviews for Statamic](https://github.com/1994limited/reviews-statamic) and [Cargo](https://builtwithcargo.dev), using only the tags in Reviews' docs, so you can see it working and copy from it.

- **Ratings on the shelf.** Each book's average and number of reviews, under its cover.
- **A book's reviews.** The average, how many of each star (each a link to just those reviews), and averages for two criteria, Story and Writing. Reviews sorted by most helpful (or newest, highest, lowest), five to a page, with photos, the shop's replies and verified purchase badges.
- **The form.** Star rating, criteria, headline, review, photos. Guests give a name and email; signed-in readers can change their review. Works without JavaScript.
- **"Was this helpful?"**, one vote each.
- **Review schema** on every book page, for star ratings in search results.
- **The Control Panel** at `/cp`: Content → Reviews, with reviews waiting, one rejected and one spam, to try moderating.
- **Verified purchases.** Jo bought two books, so their reviews of those say "Verified purchase".
- **Ready-made reviews.** `/ready-made` is the same book's reviews with `{{ reviews:display }}` and the default styles instead of the shop's own templates: two tags, taking the shop's fonts and colours.
- **SEO Pro.** The book pages' JSON-LD comes from [SEO Pro](https://statamic.com/addons/statamic/seo-pro), with the rating and latest reviews added by `{{ reviews:schema_data }}` in the products' section defaults (Tools → SEO Pro → Section Defaults → Products).

The book covers are drawn in CSS: there are no image files, and every title, author and reviewer is invented.

## Running it

You need PHP 8.3+ with GD, Composer and Node. Then:

```bash
git clone https://github.com/1994limited/reviews-statamic-demo.git
cd reviews-statamic-demo
composer setup
php artisan demo:users
php artisan demo:seed
php artisan serve
```

Then open http://localhost:8000.

`demo:users` makes two accounts, both with the password `password`:

| Account | For |
| --- | --- |
| `jo@example.com` | A reader who has bought two books. The sign-in page is filled in with it. |
| `admin@example.com` | The Control Panel, at `/cp`. |

`demo:seed` writes about 70 reviews and a few orders. Run it again with `--fresh` to start over. Two of Coral Arithmetic's reviews are imported from Trustpilot, to show how a review from elsewhere looks, and there's a Feefo source under **Reviews → Sync**, switched off. To try importing, preview `scripts/sample-export.csv` (a Yotpo export) under **Reviews → Import**.

Both only run with `APP_ENV=local`. Don't put this demo on a public server as it is: the sign-in page gives the password away.

Reviews, Cargo and SEO Pro are commercial addons. Running them locally like this needs no licence. The demo turns on Statamic Pro (`STATAMIC_PRO_ENABLED=true`), which Cargo needs, and which runs in Statamic's trial mode locally.

## Things to try

1. Open **A Field Guide to Leaving** and sort its reviews by lowest rating, or click "2 stars" in the breakdown.
2. Write a review as a guest. It waits for an editor: sign in to `/cp` as the admin and approve it, then reply to it.
3. Sign in as Jo and review **Coral Arithmetic**, which Jo bought: it's marked as a verified purchase.
4. Change the settings in **Tools → Addons → Reviews → Settings**: publish signed-in reviews straight away, turn photos off, or allow only buyers to review.

The review templates are in `resources/views/products/show.antlers.html` and `resources/views/partials/reviews.antlers.html`; the styles in `public/css/dogear.css`. The ready-made version is `resources/views/ready-made.antlers.html`.

## Screenshots

`scripts/screenshots.mjs` takes the screenshots in Reviews' docs, with Playwright and Chrome: `cd scripts && npm install && npm run screenshots` (see the script for its options).
