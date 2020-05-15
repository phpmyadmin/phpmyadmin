// Dependencies
const { After, Before, AfterAll } = require('cucumber');
const scope = require('./scope');

Before(async () => {
    /*
        console.log('Host: ' + scope.host);
        console.log('User: ' + scope.username);
        console.log('Password: ' + '*'.repeat(scope.password.length));
    */
});

After(async (options) => {
  if (options.result.status !== 'passed' && scope.context.currentPage) {
      // Do stuff when tests fail
  }
  // Here we check if a scenario has instantiated a browser and a current page
  if (scope.browser && scope.context.currentPage && scope.context.deleteCookies) {
    // if it has, find all the cookies, and delete them
    const cookies = await scope.context.currentPage.cookies();
    if (cookies && cookies.length > 0) {
      await scope.context.currentPage.deleteCookie(...cookies);
    }
    // close the web page down
    await scope.context.currentPage.close();
    // wipe the context's currentPage value
    scope.context.currentPage = null;
  }
});

AfterAll(async () => {
  // If there is a browser window open, then close it
  if (scope.browser) await scope.browser.close();
});
