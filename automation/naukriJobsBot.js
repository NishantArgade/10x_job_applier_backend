import { By, until } from "selenium-webdriver";
import fs from "fs";
import path from "path";
import {
    createLogger,
    initializeDriver,
    login,
    scrollAndClick,
    switchToTab,
    closeTabAndSwitchToMain,
    elementExists,
    setupShutdownHandlers,
    writePidFile
} from "./naukriUtils.js";
import dotenv from "dotenv";

dotenv.config();

const NAUKRI_JOBS_URL = "https://www.naukri.com/mnjuser/recommendedjobs";

const log = createLogger("jobs");

async function applyForJob(driver, jobElement, jobIndex, totalJobs) {
    log(`Processing job ${jobIndex + 1}/${totalJobs}`);

    try {
        await scrollAndClick(driver, jobElement);
        await driver.sleep(800); // Reduced from 2000ms
        const tabSwitched = await switchToTab(driver);

        // Parallel checks for faster processing
        const [chatbotExists, externalLinks] = await Promise.all([
            driver.findElements(By.id("_mtdn7v2eyChatbotContainer")).then(elements => elements.length > 0),
            driver.findElements(
                By.xpath("//a[contains(text(), 'Apply on company website') or contains(@class, 'external-apply')]")
            ).then(elements => elements.length > 0)
        ]);

        if (chatbotExists) {
            log(`Skipping job ${jobIndex + 1}: Contains chatbot container`, "WARN");
            if (tabSwitched) await closeTabAndSwitchToMain(driver);
            return false;
        }

        if (externalLinks) {
            log(`Skipping job ${jobIndex + 1}: External job application`, "WARN");
            if (tabSwitched) await closeTabAndSwitchToMain(driver);
            return false;
        }

        try {
            const applyBtn = await driver.wait(until.elementLocated(By.xpath("//button[contains(text(), 'Apply')]")), 4000);
            await driver.wait(until.elementIsVisible(applyBtn), 2000);
            await scrollAndClick(driver, applyBtn);
            log(`Applied to job ${jobIndex + 1}`);
            
            await driver.sleep(1000); // Reduced from 2000ms
            const postApplyChatbot = await driver.findElements(By.id("_mtdn7v2eyChatbotContainer"));
            if (postApplyChatbot.length > 0) {
                const closeButtons = await driver.findElements(By.css("[aria-label='close']"));
                if (closeButtons.length > 0) {
                    await driver.executeScript("arguments[0].click();", closeButtons[0]);
                }
            }
        } catch (error) {
            log(`No Apply button found for job ${jobIndex + 1}, skipping`, "WARN");
            if (tabSwitched) await closeTabAndSwitchToMain(driver);
            return false;
        }

        if (tabSwitched) await closeTabAndSwitchToMain(driver);
        return true;
    } catch (error) {
        log(`Failed to apply to job ${jobIndex + 1}: ${error.message}`, "WARN");
        try {
            await closeTabAndSwitchToMain(driver);
        } catch (e) {}
        return false;
    }
}

async function applyRecommendedJobs(driver) {
    log("Navigating to recommended jobs page");
    await driver.get(NAUKRI_JOBS_URL);
    await driver.sleep(2000); // Reduced from 5000ms

    try {
        log("Loading job listings");
        await driver.wait(until.elementLocated(By.css("article.jobTuple")), 10000); // Reduced timeout
        const jobArticles = await driver.findElements(By.css("article.jobTuple"));

        const jobsToApply = Math.min(jobArticles.length, 20); // Limit to prevent excessive runtime
        log(`Found ${jobArticles.length} jobs, will apply to first ${jobsToApply} suitable ones`);

        let appliedCount = 0;
        for (let i = 0; i < jobsToApply; i++) {
            const refreshedJobArticles = await driver.findElements(By.css("article.jobTuple"));
            if (i >= refreshedJobArticles.length) {
                log("No more jobs available", "WARN");
                break;
            }

            const jobItem = refreshedJobArticles[i];
            
            // Fast job filtering using parallel text extraction
            const [jobTitle, jobDescription] = await Promise.all([
                jobItem.findElement(By.css(".title")).getText().catch(() => ""),
                jobItem.findElement(By.css(".job-description")).getText().catch(() => "")
            ]);
            
            const complexJobTerms = ["senior", "lead", "architect", "manager", "principal"];
            const isComplexJob = complexJobTerms.some(term => 
                jobTitle.toLowerCase().includes(term) || jobDescription.toLowerCase().includes(term)
            );

            if (isComplexJob) {
                log(`Skipping job ${i + 1}: Senior position detected`, "WARN");
                continue;
            }

            const success = await applyForJob(driver, refreshedJobArticles[i], i, jobsToApply);
            if (success) appliedCount++;
            await driver.sleep(500); // Reduced inter-job delay
        }

        log(`Successfully applied to ${appliedCount} jobs`);
        return appliedCount;
    } catch (error) {
        log(`Error applying for jobs: ${error.message}`, "ERROR");
        throw error;
    }
}

async function naukriJobsBot() {
    log("Starting Naukri job application bot");
    
    const headless = process.env.NAUKRI_HEADLESS === "true";

    log(`Running with headless mode: ${headless}`);
    
    try {
        writePidFile("jobs");
    } catch (error) {
        log(`Failed to write PID file: ${error.message}`, "ERROR");
    }
    
    let driver;
    try {
        driver = await initializeDriver(headless, log);
        setupShutdownHandlers(log, driver, "jobs");
        
        await login(driver, log);
        await applyRecommendedJobs(driver);
        log("Job applications completed successfully");
    } catch (error) {
        log(`Error in Naukri job applications: ${error.message}`, "ERROR");
        throw error;
    } finally {
        if (driver) {
            log("Closing browser");
            await driver.quit();
        }
        
        // Clean up PID file
        try {
            const pidFilePath = path.join(__dirname, "..", "storage", "app", "naukri_jobs_bot.pid");
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
        await naukriJobsBot();
        process.exit(0);
    } catch (error) {
        log(`Naukri job applications failed: ${error.message}`, "ERROR");
        process.exit(1);
    }
})();
