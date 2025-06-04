import { Builder, By, Key, until } from "selenium-webdriver";
import "chromedriver";
import fs from "fs";
import os from "os";
import path from "path";
import { fileURLToPath } from "url";
import dotenv from "dotenv";

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const LOG_DIR = path.join(__dirname, "..", "storage", "logs");
const PID_DIR = path.join(__dirname, "..", "storage", "app");
const NAUKRI_LOGIN_URL = "https://www.naukri.com/nlogin/login";

[LOG_DIR, PID_DIR].forEach((dir) => {
    if (!fs.existsSync(dir)) {
        fs.mkdirSync(dir, { recursive: true });
    }
});

function getLogFile(botType) {
    return path.join(
        LOG_DIR,
        `naukri-${botType}-${new Date().toISOString().split("T")[0]}.log`
    );
}

function createLogger(botType) {
    const logFile = getLogFile(botType);

    return function log(message, level = "INFO") {
        const timestamp = new Date().toISOString();
        const logPrefix = `[${timestamp}] [${level}] [${botType}]`;
        const logMessage = `${logPrefix} ${message}\n`;

        const colors = {
            ERROR: "\x1b[31m",
            WARN: "\x1b[33m",
            INFO: "\x1b[36m",
        };
        const resetColor = "\x1b[0m";
        const color = colors[level] || colors.INFO;

        console.log(`${color}${logPrefix}${resetColor} ${message}`);

        try {
            fs.appendFileSync(logFile, logMessage);
        } catch (error) {
            console.error(`Failed to write to log file: ${error.message}`);
        }
    };
}

async function initializeDriver(headless = false, log) {
    log("Initializing Chrome WebDriver");

    try {
        const chrome = await import("selenium-webdriver/chrome.js");
        const options = new chrome.Options();

        // ✅ Create temporary profile directory
        const tempProfilePath = path.join(
            os.tmpdir(),
            `naukri-chrome-profile-${Date.now()}`
        );
        options.addArguments(`--user-data-dir=${tempProfilePath}`);
        options.addArguments("--incognito");

        // ✅ Disable password manager & services
        options.addArguments("--disable-save-password-bubble");
        options.addArguments("--disable-credentials-enable-service");
        options.addArguments("--disable-password-manager-reauthentication");
        options.addArguments("--disable-autofill-keyboard-accessory-view");
        options.addArguments("--disable-notifications");

        options.setUserPreferences({
            credentials_enable_service: false,
            "profile.password_manager_enabled": false,
        });

        if (headless) {
            options.addArguments("--headless=new");
            options.addArguments("--disable-gpu");
        }

        options.addArguments("--no-sandbox");
        options.addArguments("--disable-dev-shm-usage");
        options.addArguments("--window-size=800,600");

        const driver = new Builder()
            .forBrowser("chrome")
            .setChromeOptions(options)
            .build();

        return driver;
    } catch (error) {
        log(`Error initializing ChromeDriver: ${error.message}`, "ERROR");

        if (
            error.message.includes("ChromeDriver only supports Chrome version")
        ) {
            log(
                "Version mismatch between ChromeDriver and Chrome browser",
                "ERROR"
            );
            log(
                "Install correct ChromeDriver version: npm install chromedriver@<version>",
                "ERROR"
            );
        }

        throw error;
    }
}

async function login(driver, log) {
    log("Logging into Naukri");

    const username = process.env.NAUKRI_USERNAME;
    const password = process.env.NAUKRI_PASSWORD;

    if (!username || !password) {
        log("Naukri credentials not found in environment variables", "ERROR");
        throw new Error("Missing Naukri credentials");
    }

    await driver.get(NAUKRI_LOGIN_URL);

    try {
        await driver.wait(until.elementLocated(By.id("usernameField")), 20000);
        await driver.findElement(By.id("usernameField")).sendKeys(username);
        await driver.findElement(By.id("passwordField")).sendKeys(password);
        await driver
            .findElement(By.xpath("//button[contains(text(),'Login')]"))
            .click();
        await driver.sleep(2000);
        return true;
    } catch (error) {
        log(`Login error: ${error.message}`, "ERROR");
        throw new Error("Failed to login: " + error.message);
    }
}

async function scrollAndClick(driver, element) {
    await driver.executeScript(
        "arguments[0].scrollIntoView({block: 'center', inline: 'center'});",
        element
    );
    await driver.sleep(1000);
    await driver.executeScript("arguments[0].click();", element);
    return true;
}

async function switchToTab(driver, tabIndex = 1) {
    const windows = await driver.getAllWindowHandles();
    if (windows.length > tabIndex) {
        await driver.switchTo().window(windows[tabIndex]);
        return true;
    }
    return false;
}

async function closeTabAndSwitchToMain(driver) {
    const windows = await driver.getAllWindowHandles();
    if (windows.length > 1) {
        await driver.close();
        await driver.switchTo().window(windows[0]);
    }
}

function setupShutdownHandlers(log) {
    const handleShutdown = (signal) => {
        log(`Received ${signal} signal, shutting down gracefully`, "INFO");
        process.exit(0);
    };

    process.on("SIGINT", () => handleShutdown("SIGINT"));
    process.on("SIGTERM", () => handleShutdown("SIGTERM"));
}

function writePidFile(botType) {
    const pidFilePath = path.join(PID_DIR, `naukri_${botType}_bot.pid`);

    try {
        fs.writeFileSync(pidFilePath, process.pid.toString(), "utf8");
        return pidFilePath;
    } catch (error) {
        console.error(`Failed to write PID file: ${error.message}`);

        try {
            const fallbackPath = path.join(
                __dirname,
                `naukri_${botType}_bot.pid`
            );
            fs.writeFileSync(fallbackPath, process.pid.toString(), "utf8");
            return fallbackPath;
        } catch (fallbackError) {
            console.error(
                `Failed to write fallback PID file: ${fallbackError.message}`
            );
            return null;
        }
    }
}

export {
    createLogger,
    initializeDriver,
    login,
    scrollAndClick,
    switchToTab,
    closeTabAndSwitchToMain,
    setupShutdownHandlers,
    writePidFile,
};
