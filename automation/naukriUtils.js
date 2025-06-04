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

        // ✅ Speed optimizations
        options.addArguments("--disable-blink-features=AutomationControlled");
        options.addArguments("--disable-dev-shm-usage");
        options.addArguments("--disable-gpu");
        options.addArguments("--disable-extensions");
        options.addArguments("--disable-plugins");
        options.addArguments("--disable-images");
        options.addArguments("--disable-javascript-harmony-shipping");
        options.addArguments("--disable-background-timer-throttling");
        options.addArguments("--disable-backgrounding-occluded-windows");
        options.addArguments("--disable-renderer-backgrounding");
        options.addArguments("--disable-features=TranslateUI");
        options.addArguments("--disable-ipc-flooding-protection");
        options.addArguments("--no-sandbox");
        options.addArguments("--window-size=1200,800");

        // ✅ Disable password manager & services
        options.addArguments("--disable-save-password-bubble");
        options.addArguments("--disable-credentials-enable-service");
        options.addArguments("--disable-password-manager-reauthentication");
        options.addArguments("--disable-autofill-keyboard-accessory-view");
        options.addArguments("--disable-notifications");

        options.setUserPreferences({
            credentials_enable_service: false,
            "profile.password_manager_enabled": false,
            "profile.default_content_setting_values.notifications": 2,
            "profile.default_content_settings.popups": 0,
        });

        if (headless) {
            options.addArguments("--headless=new");
        }

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
        // Faster login with reduced timeouts
        await driver.wait(until.elementLocated(By.id("usernameField")), 10000);
        
        const usernameField = await driver.findElement(By.id("usernameField"));
        const passwordField = await driver.findElement(By.id("passwordField"));
        
        // Clear and type faster
        await usernameField.clear();
        await usernameField.sendKeys(username);
        await passwordField.clear();
        await passwordField.sendKeys(password);
        
        const loginButton = await driver.findElement(By.xpath("//button[contains(text(),'Login')]"));
        await loginButton.click();
        
        // Wait for navigation instead of fixed sleep
        await driver.wait(until.urlContains("naukri.com"), 8000);
        await driver.sleep(500); // Minimal wait for page stabilization
        
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
    await driver.sleep(300); // Reduced from 1000ms
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

// Fast utility for checking element existence without waiting
async function elementExists(driver, locator, timeout = 1000) {
    try {
        await driver.wait(until.elementLocated(locator), timeout);
        return true;
    } catch (error) {
        return false;
    }
}

// Batch process multiple elements for faster operations
async function batchProcessElements(driver, elements, processor, concurrency = 3) {
    const results = [];
    for (let i = 0; i < elements.length; i += concurrency) {
        const batch = elements.slice(i, i + concurrency);
        const batchPromises = batch.map((element, index) => 
            processor(element, i + index).catch(error => ({ error, index: i + index }))
        );
        const batchResults = await Promise.all(batchPromises);
        results.push(...batchResults);
        
        // Small delay between batches to prevent overwhelming
        if (i + concurrency < elements.length) {
            await driver.sleep(200);
        }
    }
    return results;
}

function setupShutdownHandlers(log, driver = null, botType = null) {
    const handleShutdown = async (signal) => {
        log(`Received ${signal} signal, shutting down gracefully`, "INFO");
        
        try {
            // Close browser if it exists
            if (driver) {
                log("Closing browser...", "INFO");
                await driver.quit().catch(err => 
                    log(`Error closing browser: ${err.message}`, "WARN")
                );
            }
            
            // Clean up PID file
            if (botType) {
                const pidFilePath = path.join(PID_DIR, `naukri_${botType}_bot.pid`);
                try {
                    if (fs.existsSync(pidFilePath)) {
                        fs.unlinkSync(pidFilePath);
                        log(`Cleaned up PID file: ${pidFilePath}`, "INFO");
                    }
                } catch (error) {
                    log(`Error cleaning PID file: ${error.message}`, "WARN");
                }
            }
            
            log("Shutdown complete", "INFO");
        } catch (error) {
            log(`Error during shutdown: ${error.message}`, "ERROR");
        } finally {
            process.exit(0);
        }
    };

    process.on("SIGINT", () => handleShutdown("SIGINT"));
    process.on("SIGTERM", () => handleShutdown("SIGTERM"));
    process.on("SIGKILL", () => handleShutdown("SIGKILL"));
    
    // Handle Windows-specific signals
    if (process.platform === "win32") {
        process.on("SIGBREAK", () => handleShutdown("SIGBREAK"));
    }
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
    elementExists,
    setupShutdownHandlers,
    writePidFile,
    batchProcessElements,
};
