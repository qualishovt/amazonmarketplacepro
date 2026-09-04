# User guide source

`user-guide.html` is the guide; `img/` holds the back-office screenshots it
embeds (taken on PrestaShop 9.1.5 with placeholder seller ID, shop address
and cron token). `render.js` prints it to A4 with Puppeteer:

    node render.js "C:/path/to/Amazon-Marketplace-Pro-User-Guide.pdf"

Puppeteer is not a dependency of the module; run it from a folder that has
it installed (`npm i puppeteer`), or set NODE_PATH to one.

Not part of the module ZIP, like `website/` and `relay-server/`.
