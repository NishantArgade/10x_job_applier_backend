import { By, until } from "selenium-webdriver";
import {
    NAUKRI_PROFILE_URL,
    DEFAULT_RESUME_HEADLINE,
    createLogger,
    initializeDriver,
    login,
    scrollAndClick,
    setupShutdownHandlers,
    writePidFile
} from "./naukriUtils.js";
import dotenv from "dotenv";

// Load environment variables
dotenv.config();

const log = createLogger("profile");

async function updateResumeHeadline(driver, headline = DEFAULT_RESUME_HEADLINE) {
    log("Updating resume headline");
    await driver.get(NAUKRI_PROFILE_URL);

    try {
        await driver.wait(
            until.elementLocated(By.css("#lazyResumeHead .edit.icon")),
            10000
        );
        await driver.sleep(1000);

        const editBtn = await driver.findElement(
            By.css("#lazyResumeHead .edit.icon")
        );
        await scrollAndClick(driver, editBtn);

        await driver.wait(
            until.elementLocated(By.id("resumeHeadlineTxt")),
            10000
        );
        const headlineInput = await driver.findElement(
            By.id("resumeHeadlineTxt")
        );
        await headlineInput.clear();
        await headlineInput.sendKeys(headline);

        const saveButton = await driver.wait(
            until.elementLocated(
                By.css('form[name="resumeHeadlineForm"] button[type="submit"]')
            ),
            5000
        );
        await scrollAndClick(driver, saveButton);
        await driver.sleep(2000);

        log("Resume headline updated successfully");
        return true;
    } catch (error) {
        log(`Failed to update resume headline: ${error.message}`, "ERROR");
        throw new Error("Resume headline update failed: " + error.message);
    }
}

async function naukriProfileBot() {
    log("Starting Naukri profile update bot");

    const username = process.env.NAUKRI_USERNAME;
    const password = process.env.NAUKRI_PASSWORD;
    const headless = process.env.NAUKRI_HEADLESS === "true";

    if (!username || !password) {
        log("Naukri credentials not found in environment variables", "ERROR");
        throw new Error("Missing Naukri credentials");
    }

    log(`Running with headless mode: ${headless}`);
    
    try {
        writePidFile("profile");
    } catch (error) {
        log(`Failed to write PID file: ${error.message}`, "ERROR");
    }

    setupShutdownHandlers(log);

    let driver;
    try {
        driver = await initializeDriver(headless, log);
        await login(driver, username, password, log);
        await updateResumeHeadline(driver);
        log("Profile update completed successfully");
    } catch (error) {
        log(`Error in Naukri profile update: ${error.message}`, "ERROR");
        throw error;
    } finally {
        if (driver) {
            log("Closing browser");
            await driver.quit();
        }
    }
}

(async () => {
    try {
        await naukriProfileBot();
        process.exit(0);
    } catch (error) {
        log(`Naukri profile update failed: ${error.message}`, "ERROR");
        process.exit(1);
    }
})();
