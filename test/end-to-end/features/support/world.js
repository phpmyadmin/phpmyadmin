const { Given, Then, setWorldConstructor } = require("cucumber");
const { expect } = require("chai");
const scope = require('./scope');
const puppeteer = require('puppeteer');

const World = function () {
    scope.driver = puppeteer;
    scope.context = {};
    scope.host = process.env.TESTSUITE_URL || 'http://localhost/phpmyadmin';
    // Trailing slash is not allowed
    scope.host = scope.host.substr(-1) === '/' ? scope.host.substr(0, scope.host.length - 1) : scope.host;
    scope.username = process.env.TESTSUITE_USER || 'root';
    scope.password = process.env.TESTSUITE_PASSWORD || '';
    scope.windowWidth = 2024;
    scope.windowHeight = 800;
};

setWorldConstructor(World);
//TODO: implement error handling

const browsePage = async (path) => {
    var headless = false;
    if (process.env.TESTSUITE_HEADLESS) {
        headless = true;
    }
    if (!scope.browser)
        scope.browser = await scope.driver.launch({
            headless: headless, defaultViewport: {
                width: scope.windowWidth,
                height: scope.windowHeight
            },
            slowMo: parseInt(process.env.TESTSUITE_SLOWMO || 50, 10)
        });
    scope.context.currentPage = await scope.browser.newPage();
    const url = scope.host + path;
    const visit = await scope.context.currentPage.goto(url, {
        waitUntil: 'domcontentloaded'
    });
    return visit;
};

const fillFieldwithText = async (field, contents) => {
    const elementHandle = await scope.context.currentPage.$(field);
    if (! elementHandle) {
        console.error('No element found for ' + field);
        return;
    }
    // To long strings can make the operation timeout
    await elementHandle.type(contents, { delay: 10 });// Types slower, like a user
};

const fillCodeMirror = async (contents, index = 0) => {
    await scope.context.currentPage.evaluate((contents, index) => {
        $('.cm-s-default')[index].CodeMirror.setValue(contents);
    }, contents, index);
};

const emptyField = async (field) => {
    const elementHandle = await scope.context.currentPage.$(field);
    await scope.context.currentPage.evaluate((element) => {
        if (element) {
            element.value = '';
        }
    }, elementHandle);
};

const clickElement = async (field) => {
    await scope.context.currentPage.click(field);
};

const waitForPartialText = async (field, fieldType = 'a', timeout = 30000) => {
    field = field.replace(/'/g, '&quot;');
    const xpath = '//' + fieldType + '[contains(., "' + field + '")]';
    await scope.context.currentPage.waitForXPath(
        xpath,
        {
            timeout: timeout
        }
    );
};

const clickXpath = async (xpath) => {
    await scope.context.currentPage.evaluate(
        (xpath) => {
            var elem = document.evaluate(xpath, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE).singleNodeValue;
            if (elem) {
                elem.click();
            } else {
                console.error('Not xpath found:' + xpath);
            }
        },
        xpath
    );
};

const clickElementPartial = async (field, fieldType = 'a') => {
    field = field.replace('"\'"', '&quot;');
    const xpath = '//' + fieldType + '[contains(., \'' + field + '\')]';
    await clickXpath(xpath);
};

const clickElementValue = async (field, fieldType = 'a', attribute = '@value') => {
    field = field.replace('"\'"', '&quot;');
    const xpath = '//' + fieldType + '[' + attribute + '=\'' + field + '\']';
    await clickXpath(xpath);
};

const clickElementTitle = async (field, fieldType = 'a') => {
    return await clickElementValue(field, fieldType, '@title');
};

const clickElementContents = async (field, fieldType = 'a') => {
    field = field.replace('"\'"', '&quot;');
    const xpath = '//' + fieldType + '[text()=\'' + field + '\']';
    await clickXpath(xpath);
};

const focusPmaLogo = async () => {
    // Move the cursor onto the logo to hide the more menu
    await scope.context.currentPage.hover('#imgpmalogo');
};

const clickTab = async (field) => {
    const submenuMore = await scope.context.currentPage.$('.submenu');
    if (submenuMore && await submenuMore.isIntersectingViewport()) {// Element is visible
        // Expand the more menu
        await scope.context.currentPage.hover('#topmenu > li.submenu > a');
    }
    await clickXpath('//a[(@class="tab" or @class="tabactive") and contains(., "' + field + '")]');
    await focusPmaLogo();
};

const checkInputContains = async (field, contents) => {
    const elementHandle = await scope.context.currentPage.$(field);
    expect(elementHandle).to.be.an('object');
    // https://github.com/GoogleChrome/puppeteer/issues/3051#issuecomment-411647065
    const text = await scope.context.currentPage.evaluate(element => element.value, elementHandle);
    expect(text).to.equal(contents);
};

const checkInputNotContains = async (field, contents) => {
    const elementHandle = await scope.context.currentPage.$(field);
    // https://github.com/GoogleChrome/puppeteer/issues/3051#issuecomment-411647065
    const text = await scope.context.currentPage.evaluate(element => element.value, elementHandle);
    expect(text).to.not.equal(contents);
};

const waitForRedirect = async (path) => {
    const url = scope.host + path;
    await scope.context.currentPage
        .waitFor((url) => document.location.href === url, {
            timeout: 10000
        }, url);
};

const waitForAjaxToEnd = async () => {
    await scope.context.currentPage
        .waitFor(() => AJAX.active === false, {
            timeout: 10000
        });
};


const canNotFindContent = async (content, selector) => {
    const bodyHandle = await scope.context.currentPage.$(selector);
    const html = await scope.context.currentPage.evaluate(body => body.innerHTML, bodyHandle);
    await bodyHandle.dispose();
    expect(html).to.not.contain(content);
};

const canFindContent = async (content, selector, attr = 'innerHTML') => {
    const bodyHandle = await scope.context.currentPage.$(selector);
    const html = await scope.context.currentPage.evaluate((body, attr) => body[attr], bodyHandle, attr);
    await bodyHandle.dispose();
    expect(html).to.contain(content);
};

const readContentIntoContextVariable = async (selector, variableName, attr = 'value') => {
    const bodyHandle = await scope.context.currentPage.$(selector);
    const html = await scope.context.currentPage.evaluate((body, attr) => body[attr], bodyHandle, attr);
    await bodyHandle.dispose();
    scope.context[variableName] = html;
};

const checkCheckboxForLabel = async (content) => {
    content = content.replace(/'/g, '&quot;');
    await scope.context.currentPage.evaluate(
        (content) => {
            // Check the checkbof that is linked to the label
            document.getElementById(
                document.evaluate('//label[contains(., "' + content + '")]',
                    document,
                    null,
                    XPathResult.FIRST_ORDERED_NODE_TYPE
                ).singleNodeValue.getAttribute("for")
            ).checked = true;
        },
        content
    );
};

const confirmDialogThatSays = async (content) => {
    scope.context.currentPage.on('dialog', async dialog => {
        expect(dialog.message()).to.equal(content);
        await dialog.dismiss();
    });
};

/**
 * Get table cell data by the class attribute of the table
 * @param {string} tableClass The table css class
 * @param {number} row The table row number
 * @param {number} column The table column number
 */
const getCellByTableClass = async(tableClass, row, column) => {
    const selector = 'table.' + tableClass + ' tbody tr:nth-child('+ row + ') td:nth-child(' + column + ')';
    const bodyHandle = await scope.context.currentPage.$(selector);
    const html = await scope.context.currentPage.evaluate((body) => (body !== null) ? body.innerText : '', bodyHandle);
    if (bodyHandle) {
        await bodyHandle.dispose();
    }
    return html.trim();
};

Given('the homepage as an already logged in user', { timeout: 40000 }, async () => {
    await browsePage('/');
    await waitForPartialText("Version information", "*");
});

Given('that I am logged in as the test user', { timeout: 40000 }, async () => {
    await browsePage('/');
    await waitForPartialText("Welcome to phpMyAdmin", "*");
    await emptyField("#input_username");
    await fillFieldwithText("#input_username", scope.context.username);
    await emptyField("#input_password");
    await fillFieldwithText("#input_password", scope.context.password);
    await clickElement("#input_go");
    await waitForPartialText("Databases");
    await waitForPartialText("Version information", "*");
});

Given('that I am logged in as a created user', { timeout: 40000 }, async () => {
    let username = '__test__selenium__pma_suite';
    scope.context.userForChangePasswordUser = username;
    let password = 'toor';
    scope.context.passwordForChangePasswordUser = password;
    await browsePage('/');
    await emptyField("#input_username");
    await fillFieldwithText("#input_username", scope.context.username);
    await emptyField("#input_password");
    await fillFieldwithText("#input_password", scope.context.password);
    await clickElement("#input_go");
    await waitForPartialText("Databases");
    await clickTab("SQL");
    await waitForPartialText("Rollback when finished", "*");
    await fillCodeMirror(
        "DROP USER IF EXISTS '" + username + "'@'%';\n" +
        "CREATE USER '" + username + "'@'%' IDENTIFIED BY '" + password + "';\n" +
        "GRANT ALL PRIVILEGES ON *.* TO '" + username + "'@'%';\n" +
        "FLUSH PRIVILEGES;"
        );
    await clickElementValue('Go', 'input');
    await waitForPartialText("empty result set", "*");
    await clickElementTitle('Log out');
    await waitForPartialText("Welcome to phpMyAdmin", "*");
    await emptyField("#input_username");
    await fillFieldwithText("#input_username", username);
    await emptyField("#input_password");
    await fillFieldwithText("#input_password", password);
    await clickElement("#input_go");
    await waitForPartialText("Databases");
});

Then('I drop the created user', { timeout: 20000 }, async () => {
    await clickElementTitle('Log out');
    await waitForPartialText("Welcome to phpMyAdmin", "*");
    await emptyField("#input_username");
    await fillFieldwithText("#input_username", scope.context.userForChangePasswordUser);
    await emptyField("#input_password");
    await fillFieldwithText("#input_password", scope.context.passwordForChangePasswordUser);
    await clickElement("#input_go");
    await waitForPartialText("SQL");
    await clickTab("SQL");
    await waitForPartialText("Rollback when finished", "*");
    await fillCodeMirror("DROP USER IF EXISTS '" + scope.context.userForChangePasswordUser + "'@'%';");
    await clickElementValue('Go', 'input');
    await waitForPartialText("empty result set", "*");
});

Given('user credentials for cookie login', function () {
    scope.context.username = scope.username;
    scope.context.password = scope.password;
});

Then('I logout', async function () {
    await clickElementTitle('Log out');
});

Given('that I browse {string}', browsePage);

Then(/^I read value of "(.*)" into <(.*)>$/, { timeout: 8000 }, async (selector, variableName) => {
    await readContentIntoContextVariable(selector, variableName);
});

Then(/^I can not find "(.*)" into "(.*)"$/, canNotFindContent);

Then(/^I can find "(.*)" into "(.*)"$/, async (text, contentSelector) => {
    await canFindContent(text, contentSelector);
});

Then(/^I should see a success message "(.*)"$/, { timeout: 80000 }, async (text) => {
    await canFindContent(text, 'div.success');
});

Then("I wait for request to end", { timeout: 8000 }, async () => {
    await waitForAjaxToEnd();
});

Then(/^I fill SQL CodeMirror with "(.*)"$/, { timeout: 15000 }, async (contents) => {
    await fillCodeMirror(contents);
    await waitForPartialText(contents, 'div');
    await canFindContent(contents, '#sqlquerycontainerfull', 'innerText');
});

Then(/^I fill ([A-Za-z0-9\-_#\.\$]+) with "(.*)"$/, async (inputName, elementSelector) => {
    await fillFieldwithText(inputName, elementSelector);
});

Then(/^I fill ([A-Za-z0-9\-_#\.\$]+) with <(.*)>$/, async (inputName, variableName) => {
    await fillFieldwithText(inputName, scope.context[variableName]);
});

Then(/^I empty ([A-Za-z0-9\-_#\.\$]+)$/, emptyField);

Then(/^the input (.*) should contain "(.*)"$/,  async (inputName, contents) => {
    await checkInputContains(inputName, contents || '');
});

Then(/^the input (.*) should contain <(.*)>$/, async (inputName, variableName) => {
    await checkInputContains(inputName, scope.context[variableName]);
});

Then(/^the input (.*) should not contain "(.*)"$/,  async (inputName, contents) => {
    await checkInputNotContains(inputName, contents || '');
});


Then(/^I click ([A-Za-z0-9\-_#\.\$]+)$/, clickElement);

Then(/^I wait for a link that contains "(.*)"$/, { timeout: 20000 }, async (text) => {
    await waitForPartialText(text);
});

Then(/^I click a link "(.*)"$/, { timeout: 8000 }, async (text) => {
    await clickElementValue(text, 'a', 'text()');
});

Then(/^I wait for a text that contains "(.*)"$/, { timeout: 20000 }, async (text) => {
    await waitForPartialText(text, "*");
});

Then(/^I click a link that contains "(.*)"$/, { timeout: 8000 }, async (text) => {
    await clickElementPartial(text);
});

Then(/^I click an input button "(.*)"$/, { timeout: 8000 }, async (text) => {
    await clickElementValue(text, 'input');
});

Then(/^I click a button "(.*)"$/, { timeout: 8000 }, async (text) => {
    await clickElementContents(text, 'button');
});

Then(/^I click tab "(.*)"$/, { timeout: 8000 }, clickTab);

Then('I focus the logo', focusPmaLogo);

Then(/^I check the checkbox for the label "(.*)"$/, checkCheckboxForLabel);

Then(/^I check the radio for the label "(.*)"$/, checkCheckboxForLabel);

// cucumber timeout is in ms (8000 ms = 8s)
Then(/^I am redirected to "(.*)"$/, { timeout: 8000 }, waitForRedirect);

Then(/^I will confirm a dialog that says "(.*)"$/, confirmDialogThatSays);

/**
 * @param {DataTable} dataTable The cucumber-js dataTable
 */
Then(/^I see the following data results in "(.*)"$/, async (tableClass, dataTable) => {
    const expectedResults = dataTable.rows().map((lines) => lines.map((content) => content));
    const foundResults = await Promise.all(expectedResults.map(async (lines, rowNumber) => {
        return await Promise.all(
            lines.map(async (_, colNumber) => await getCellByTableClass(tableClass, rowNumber+1, colNumber+1))
        );
    }));
    expect(expectedResults).to.deep.equal(foundResults);
});
