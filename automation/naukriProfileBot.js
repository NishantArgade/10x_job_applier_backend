import { By, until } from "selenium-webdriver";
import fs from "fs";
import path from "path";
import {
    createLogger,
    initializeDriver,
    login,
    scrollAndClick,
    setupShutdownHandlers,
    writePidFile,
} from "./naukriUtils.js";
import dotenv from "dotenv";

dotenv.config();

const NAUKRI_PROFILE_URL = "https://www.naukri.com/mnjuser/profile?action=modalOpen";
const DEFAULT_RESUME_HEADLINE = "Full Stack Developer | React, Node.js | Open to Remote | Immediate Joiner";

const log = createLogger("profile");

async function updateResumeHeadline(
    driver,
    headline = DEFAULT_RESUME_HEADLINE
) {
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

    const headless = process.env.NAUKRI_HEADLESS === "true";

    log(`Running with headless mode: ${headless}`);

    try {
        writePidFile("profile");
    } catch (error) {
        log(`Failed to write PID file: ${error.message}`, "ERROR");
    }

    let driver;
    try {
        driver = await initializeDriver(headless, log);
        setupShutdownHandlers(log, driver, "profile");
        
        await login(driver, log);
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
        
        // Clean up PID file
        try {
            const pidFilePath = path.join(__dirname, "..", "storage", "app", "naukri_profile_bot.pid");
            if (fs.existsSync(pidFilePath)) {
                fs.unlinkSync(pidFilePath);
            }
        } catch (error) {
            log(`Error cleaning PID file: ${error.message}`, "WARN");
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
