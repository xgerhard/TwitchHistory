## TwitchHistory

Inspired by https://twitter.com/CohhCarnage/status/1217059067312721920.

While this does not do exactly that.. for now just gathering data by experimenting with the Twitch webhooks.

Let's see if there is a nice way to display this info, eventually maybe use it in an extension or embed for stream panels?

## How to join?
While there is not much to see yet, sign in here, so the app can register webhooks for your account and start collecting historical stream data:

https://thistory.2g.be/login

## Development
1. Get the repository: `git clone https://github.com/xgerhard/twitchhistory`
2. From the twitchhistory folder run `composer install`
3. Rename `.env.example` to `.env` and set your database details
4. Run `php artisan key:generate` to set an app key
5. Run `php artisan migrate` to install the required tables
6. Run `php artisan db:seed` to seed the tables with test data
7. Start a local webserver: `php -S localhost:8080`
8. The test data should now be available at: localhost:8080/stats/49056910

To use the Twitch API and/or Twitch OAuth login, you'll have to register an app at: https://dev.twitch.tv/
And add these variables to your .env file:
`TWITCH_KEY=XXX`
`TWITCH_SECRET=XXX`
`TWITCH_REDIRECT_URI=https://domain.com/login/callback`