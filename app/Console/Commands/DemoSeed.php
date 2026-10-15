<?php

namespace App\Console\Commands;

use DuncanMcClean\Cargo\Facades\Cart;
use DuncanMcClean\Cargo\Facades\Order;
use DuncanMcClean\Cargo\Facades\PaymentGateway;
use DuncanMcClean\Cargo\Orders\Actions\CreateOrderFromCart;
use DuncanMcClean\Cargo\Orders\OrderStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use NineteenNinetyFour\Reviews\Review;
use NineteenNinetyFour\Reviews\ReviewQuery;
use NineteenNinetyFour\Reviews\Reviews;
use NineteenNinetyFour\Reviews\ReviewStatus;
use Statamic\Facades\Entry;
use Statamic\Facades\User;

/**
 * Fills Dog-ear Books with made-up readers' reviews, some waiting to be
 * checked, some rejected or spam, and a few orders, so Jo's reviews of
 * books they bought show as verified.
 */
class DemoSeed extends Command
{
    protected $signature = 'demo:seed {--fresh : Remove the demo reviews and orders first}';

    protected $description = 'Fill the demo shop with made-up reviews and orders (local only)';

    public function handle(Reviews $reviews): int
    {
        if (! app()->environment('local')) {
            $this->components->error('The demo data is only for local sites (APP_ENV=local).');

            return self::FAILURE;
        }

        // Nobody is emailed while seeding.
        config(['mail.default' => 'array']);

        $existing = $reviews->repository()->count(ReviewQuery::make());

        if ($existing && ! $this->option('fresh')) {
            $this->components->warn('The demo reviews are already there. Run `php artisan demo:seed --fresh` to make them again.');

            return self::SUCCESS;
        }

        if ($this->option('fresh')) {
            $reviews->repository()->all()->each(fn (Review $review) => $reviews->delete($review));
            Order::all()->each->delete();
            Cart::all()->each->delete();
        }

        mt_srand(1994);

        $books = Entry::query()->where('collection', 'products')->orderBy('title')->get();
        $jo = User::findByEmail('jo@example.com');

        // Jo bought two books, a month ago (ones in stock).
        $inStock = $books->filter(fn ($book) => $this->lineItem($book) !== null)->values();
        $bought = $inStock->take(2);
        $order = $this->order(30, $jo ?? ['name' => 'Jo Reader', 'email' => 'jo@example.com'], $bought->map(fn ($b) => $this->lineItem($b))->all());

        // Some guests bought books too.
        $ada = $this->order(21, ['name' => 'Ada Lovelace', 'email' => 'ada@example.com'], [$this->lineItem($inStock[2])]);

        $people = ['Ada Lovelace', 'Grace Hopper', 'Alan Turing', 'Katherine Johnson', 'Mary Somerville', 'Hedy Lamarr', 'Tim Berners-Lee', 'Radia Perlman', 'Joan Clarke', 'Dorothy Vaughan', 'Annie Easley', 'Karen Spärck Jones', 'Margaret Hamilton', 'Charles Babbage'];

        $good = [
            ['Couldn’t put it down', 'Read it in two sittings. The ending caught me completely off guard, in the best way.'],
            ['A quiet wonder', "Gentle, strange and very funny in places.\n\nI’ve already bought a second copy for my sister."],
            ['Beautifully written', 'Every page has a sentence I wanted to underline.'],
            ['Perfect for a long train journey', 'Short chapters, lovely pace. The paperback is a nice size for a coat pocket too.'],
            ['Book club loved it', 'Eight of us, and for once we all agreed. Lots to talk about.'],
        ];

        $middling = [
            ['Good, not great', 'The middle third drags a little, but it picks up again.'],
            ['Worth reading', 'Not quite as good as the reviews made out, but I’m glad I read it.'],
        ];

        $poor = [
            ['Not for me', 'I wanted to like it more than I did. The characters never quite came alive for me.'],
            ['Arrived damaged', 'The story was fine but the cover was bent when it arrived.'],
        ];

        $made = 0;

        foreach ($books as $i => $book) {
            $count = [9, 6, 4, 7, 3, 5, 2, 8, 0, 4][$i % 10];

            for ($n = 0; $n < $count; $n++) {
                $roll = mt_rand(1, 100);
                [$rating, $pool] = $roll > 35 ? [mt_rand(4, 5), $good] : ($roll > 12 ? [3, $middling] : [mt_rand(1, 2), $poor]);
                [$title, $body] = $pool[array_rand($pool)];
                $name = $people[($i * 3 + $n) % count($people)];

                $review = $this->review($book, $rating, $title, $body, $name, daysAgo: mt_rand(2, 120), ratings: ['story' => max(1, min(5, $rating + mt_rand(-1, 1))), 'writing' => max(1, min(5, $rating + mt_rand(-1, 0)))]);
                $this->at($review->createdAt->diffInDays(now(), true), fn () => $reviews->approve($review));
                $review->helpful = mt_rand(0, 100) > 60 ? mt_rand(1, 14) : 0;
                $review->voters = array_map(fn ($v) => 'seed-'.$review->id.'-'.$v, range(1, $review->helpful)) ?: [];
                $review->helpful = count($review->voters);
                $reviews->repository()->save($review);
                $made++;
            }
        }

        // A couple with the shop's reply.
        $first = $reviews->repository()->get(ReviewQuery::make()->rating([1, 2])->approved(), 2);
        foreach ($first as $review) {
            $reviews->reply($review, 'Sorry to hear that. Thank you for being honest: we’ve passed this on, and if your copy arrived damaged, reply to your order email and we’ll send another.', User::findByEmail('admin@example.com'));
        }

        // Ada's verified review of the book she bought.
        $ada_review = $this->review($inStock[2], 5, 'Bought it for the cover, stayed for the story', 'Lovely edition, and the story lives up to it.', 'Ada Lovelace', daysAgo: 10, email: 'ada@example.com', order: $ada->id());
        $reviews->approve($ada_review);
        $made++;

        // Waiting to be checked, and the ones an editor turned down.
        $this->review($books[0], 4, 'A slow start, then wonderful', "Give it fifty pages.\nYou won’t regret it.", 'Radia Perlman', daysAgo: 1);
        $this->review($books[3], 2, 'Too long', 'Could have lost a hundred pages.', 'Charles Babbage', daysAgo: 0);
        $this->review($books[1], 5, null, null, 'Hedy Lamarr', daysAgo: 0);
        $rejected = $this->review($books[4], 1, 'Wrong book', 'This review is about a different book entirely.', 'Ben Mistake', daysAgo: 6);
        $reviews->reject($rejected, 'About a different book.');
        $spam = $this->review($books[5], 5, 'Cheap watches', 'Best prices on watches at www.watches-cheap.example', 'Totally Real', daysAgo: 3);
        $reviews->spam($spam);
        $made += 5;

        $this->components->info(sprintf('Made %d reviews of %d books, and %d orders.', $made, $books->count(), Order::query()->count()));
        $this->line('  Jo (jo@example.com / password) bought "'.$bought->pluck('title')->join('" and "').'", so their reviews of those are verified.');

        return self::SUCCESS;
    }

    protected function review($book, int $rating, ?string $title, ?string $body, string $name, int $daysAgo, ?string $email = null, ?string $order = null, array $ratings = []): Review
    {
        return $this->at($daysAgo, function () use ($book, $rating, $title, $body, $name, $email, $order, $ratings) {
            $review = Review::make();
            $review->entry = $book->id();
            $review->site = 'default';
            $review->rating = $rating;
            $review->ratings = $ratings;
            $review->title = $title;
            $review->body = $body;
            $review->name = $name;
            $review->email = $email ?? str($name)->ascii()->lower()->replace(' ', '.').'@example.com';
            $review->verified = $order !== null;
            $review->order = $order;
            $review->status = ReviewStatus::Pending;

            app(Reviews::class)->save($review);

            return $review;
        });
    }

    /**
     * A line item for the book, in an edition that's in stock, or null.
     */
    protected function lineItem($book): ?array
    {
        $variants = $book->get('product_variants');

        if (is_array($variants)) {
            $option = collect($variants['options'] ?? [])->first(fn ($o) => ($o['stock'] ?? 1) > 0);

            return $option ? ['product' => $book->id(), 'variant' => $option['key'], 'quantity' => 1] : null;
        }

        return $book->get('stock') === 0 ? null : ['product' => $book->id(), 'quantity' => 1];
    }

    protected function order(int $daysAgo, mixed $customer, array $lines): \DuncanMcClean\Cargo\Contracts\Orders\Order
    {
        return $this->at($daysAgo, function () use ($customer, $lines) {
            $cart = Cart::make()
                ->customer($customer)
                ->set('billing_address', ['line_1' => '1 Demo Street', 'city' => 'London', 'postcode' => 'N1 1AA', 'country' => 'GBR'])
                ->set('shipping_address', ['line_1' => '1 Demo Street', 'city' => 'London', 'postcode' => 'N1 1AA', 'country' => 'GBR']);

            foreach ($lines as $line) {
                $cart->lineItems()->create($line);
            }

            $cart->save();
            $cart = Cart::find($cart->id());

            $order = app(CreateOrderFromCart::class)->handle($cart, PaymentGateway::find('dummy'));
            $order->status(OrderStatus::Shipped)->save();
            $cart->delete();

            return $order;
        });
    }

    protected function at(int|float $daysAgo, \Closure $callback): mixed
    {
        Carbon::setTestNow(now()->subDays((int) $daysAgo)->setTime(mt_rand(8, 21), mt_rand(0, 59)));

        try {
            return $callback();
        } finally {
            Carbon::setTestNow();
        }
    }
}
