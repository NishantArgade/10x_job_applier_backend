import { Builder, By, Key, until } from "selenium-webdriver";
import "chromedriver";
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";
import dotenv from "dotenv";

// Constants
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const NAUKRI_LOGIN_URL = "https://www.naukri.com/nlogin/login";
const NAUKRI_PROFILE_URL =
    "https://www.naukri.com/mnjuser/profile?action=modalOpen";
const NAUKRI_JOBS_URL = "https://www.naukri.com/mnjuser/recommendedjobs";
const DEFAULT_JOB_TITLE = "Software Developer";
const DEFAULT_RESUME_HEADLINE =
    "Full Stack Developer | React, Node.js | Open to Remote | Immediate Joiner";

dotenv.config();

// Setup logging
const logDir = path.join(__dirname, "..", "storage", "logs");
if (!fs.existsSync(logDir)) {
    fs.mkdirSync(logDir, { recursive: true });
}

function getLogFile(botType) {
    return path.join(
        logDir,
        `naukri-${botType}-${new Date().toISOString().split("T")[0]}.log`
    );
}

function createLogger(botType) {
    const logFile = getLogFile(botType);
    
    return function log(message, level = "INFO") {
        const timestamp = new Date().toISOString();
        const logPrefix = `[${timestamp}] [${level}] [${botType}]`;
        const logMessage = `${logPrefix} ${message}\n`;

        // Add color to console output based on level
        let consoleMessage;
        switch (level) {
            case "ERROR":
                consoleMessage = `\x1b[31m${logPrefix}\x1b[0m ${message}`;
                break;
            case "WARN":
                consoleMessage = `\x1b[33m${logPrefix}\x1b[0m ${message}`;
                break;
            default:
                consoleMessage = `\x1b[36m${logPrefix}\x1b[0m ${message}`;
        }

        console.log(consoleMessage);

        try {
            fs.appendFileSync(logFile, logMessage);
        } catch (error) {
            console.error(`Failed to write to log file: ${error.message}`);
        }
    };
}

// Initialize WebDriver
async function initializeDriver(headless = false, log) {
    log("Initializing Chrome WebDriver");

    try {
        // Use the specific ChromeDriver version based on your installed Chrome
        const chrome = await import("selenium-webdriver/chrome.js");
        const options = new chrome.Options();

        if (headless) {
            options.addArguments("--headless=new"); // Use new headless mode
            options.addArguments("--disable-gpu");
        }

        options.addArguments("--no-sandbox");
        options.addArguments("--disable-dev-shm-usage");
        options.addArguments("--window-size=1920,1080");

        // Try to get Chrome version
        log("Checking Chrome browser version...");

        // Create the driver with these options
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
                "There is a version mismatch between ChromeDriver and your Chrome browser.",
                "ERROR"
            );
            log(
                "Please install the correct version of ChromeDriver that matches your Chrome version.",
                "ERROR"
            );
            log(
                "Run: npm install chromedriver@<version-number> --save",
                "ERROR"
            );
        }

        throw error;
    }
}

// Login to Naukri
async function login(driver, username, password, log) {
    log("Logging into Naukri");

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

// Helper function for scrolling and clicking elements
async function scrollAndClick(driver, element) {
    await driver.executeScript(
        "arguments[0].scrollIntoView({block: 'center', inline: 'center'});",
        element
    );
    await driver.sleep(1000);
    await driver.executeScript("arguments[0].click();", element);
    return true;
}

// Handle tab switching
async function switchToTab(driver, tabIndex = 1) {
    const windows = await driver.getAllWindowHandles();
    if (windows.length > tabIndex) {
        await driver.switchTo().window(windows[tabIndex]);
        return true;
    }
    return false;
}

// Close current tab and switch back to main window
async function closeTabAndSwitchToMain(driver) {
    const windows = await driver.getAllWindowHandles();
    if (windows.length > 1) {
        await driver.close();
        await driver.switchTo().window(windows[0]);
    }
}

// Handle graceful shutdown
function setupShutdownHandlers(log) {
    process.on('SIGINT', async () => {
        log("Received SIGINT signal, shutting down gracefully", "INFO");
        process.exit(0);
    });

    process.on('SIGTERM', async () => {
        log("Received SIGTERM signal, shutting down gracefully", "INFO");
        process.exit(0);
    });
}

// Write PID to file
function writePidFile(botType) {
    try {
        // Use an absolute path to ensure path resolution works properly across systems
        const pidFilePath = path.resolve(__dirname, '..', 'storage', 'app', `naukri_${botType}_bot.pid`);
        console.log(`Writing PID ${process.pid} to file: ${pidFilePath}`);
        
        // Create directory if it doesn't exist
        const pidDir = path.dirname(pidFilePath);
        if (!fs.existsSync(pidDir)) {
            fs.mkdirSync(pidDir, { recursive: true });
            console.log(`Created directory: ${pidDir}`);
        }
        
        // Write PID to file with explicit encoding
        fs.writeFileSync(pidFilePath, process.pid.toString(), 'utf8');
        
        // Verify the file was created
        if (fs.existsSync(pidFilePath)) {
            const readPid = fs.readFileSync(pidFilePath, 'utf8');
            console.log(`PID file written successfully and verified. PID: ${readPid}`);
        } else {
            console.error('Failed to verify PID file was created.');
        }
        
        // Also write the PID to a Windows-friendly location as backup
        const backupPidPath = path.join(__dirname, `naukri_${botType}_bot.pid`);
        fs.writeFileSync(backupPidPath, process.pid.toString(), 'utf8');
        console.log(`Backup PID file written to: ${backupPidPath}`);
        
        return pidFilePath;
    } catch (error) {
        console.error(`Failed to write PID file: ${error.message}`);
        console.error(error.stack);
        
        // Try to write to the bot's own directory as a fallback
        try {
            const fallbackPath = path.join(__dirname, `naukri_${botType}_bot.pid`);
            fs.writeFileSync(fallbackPath, process.pid.toString(), 'utf8');
            console.log(`Fallback PID file written to: ${fallbackPath}`);
            return fallbackPath;
        } catch (fallbackError) {
            console.error(`Failed to write fallback PID file: ${fallbackError.message}`);
            return null;
        }
    }
}

// Get configuration from environment variables
function getConfig() {
    return {
        username: process.env.NAUKRI_USERNAME,
        password: process.env.NAUKRI_PASSWORD,
        jobTitle: process.env.NAUKRI_JOB_TITLE || DEFAULT_JOB_TITLE,
        location: process.env.NAUKRI_LOCATION || "",
        headless: process.env.NAUKRI_HEADLESS === "true",
    };
}

export {
    NAUKRI_LOGIN_URL,
    NAUKRI_PROFILE_URL,
    NAUKRI_JOBS_URL,
    DEFAULT_JOB_TITLE,
    DEFAULT_RESUME_HEADLINE,
    createLogger,
    initializeDriver,
    login,
    scrollAndClick,
    switchToTab,
    closeTabAndSwitchToMain,
    setupShutdownHandlers,
    writePidFile,
    getConfig
};
