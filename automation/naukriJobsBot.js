import { By, until } from "selenium-webdriver";
import {
    NAUKRI_JOBS_URL,
    createLogger,
    initializeDriver,
    login,
    scrollAndClick,
    switchToTab,
    closeTabAndSwitchToMain,
    setupShutdownHandlers,
    writePidFile
} from "./naukriUtils.js";
import dotenv from "dotenv";

dotenv.config();
const log = createLogger("jobs");

async function applyForJob(driver, jobElement, jobIndex, totalJobs) {
    log(`Processing job ${jobIndex + 1}/${totalJobs}`);

    try {
        await scrollAndClick(driver, jobElement);
        await driver.sleep(2000);
        const tabSwitched = await switchToTab(driver);

        // Check for chatbot or external job indicators
        const chatbotExists = await driver.findElements(By.id("_mtdn7v2eyChatbotContainer")).then(elements => elements.length > 0);
        if (chatbotExists) {
            log(`Skipping job ${jobIndex + 1}: Contains chatbot container`, "WARN");
            if (tabSwitched) await closeTabAndSwitchToMain(driver);
            return false;
        }

        const externalLinks = await driver.findElements(
            By.xpath("//a[contains(text(), 'Apply on company website') or contains(@class, 'external-apply')]")
        ).then(elements => elements.length > 0);
        if (externalLinks) {
            log(`Skipping job ${jobIndex + 1}: External job application`, "WARN");
            if (tabSwitched) await closeTabAndSwitchToMain(driver);
            return false;
        }

        // Try to apply
        try {
            const applyBtn = await driver.wait(
                until.elementLocated(By.xpath("//button[contains(text(), 'Apply')]")), 
                5000
            );
            await driver.wait(until.elementIsVisible(applyBtn), 3000);
            await scrollAndClick(driver, applyBtn);
            log(`Applied to job ${jobIndex + 1}`);
            
            // Handle popup if needed
            await driver.sleep(2000);
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
        return true;    } catch (error) {
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
    await driver.sleep(5000);

    try {
        log("Loading job listings");
        await driver.wait(until.elementLocated(By.css("article.jobTuple")), 15000);
        const jobArticles = await driver.findElements(By.css("article.jobTuple"));

        const jobsToApply = jobArticles.length;
        log(`Found ${jobsToApply} jobs, will apply to suitable ones`);

        let appliedCount = 0;
        for (let i = 0; i < jobsToApply; i++) {
            const refreshedJobArticles = await driver.findElements(By.css("article.jobTuple"));
            if (i >= refreshedJobArticles.length) {
                log("No more jobs available", "WARN");
                break;
            }

            // Filter out complex jobs
            const jobItem = refreshedJobArticles[i];
            const jobTitle = await jobItem.findElement(By.css(".title")).getText().catch(() => "");
            const jobDescription = await jobItem.findElement(By.css(".job-description")).getText().catch(() => "");
            
            const complexJobTerms = ["senior", "lead", "architect", "manager"];
            const isComplexJob = complexJobTerms.some(term => 
                jobTitle.toLowerCase().includes(term) || jobDescription.toLowerCase().includes(term)
            );

            if (isComplexJob) {
                log(`Skipping job ${i + 1}: Senior position detected`, "WARN");
                continue;
            }

            const success = await applyForJob(driver, refreshedJobArticles[i], i, jobsToApply);
            if (success) appliedCount++;
            await driver.sleep(1000);
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
    
    const username = process.env.NAUKRI_USERNAME;
    const password = process.env.NAUKRI_PASSWORD;
    const headless = process.env.NAUKRI_HEADLESS === "true";
    
    if (!username || !password) {
        log("Naukri credentials not found in environment variables", "ERROR");
        throw new Error("Missing Naukri credentials");
    }

    log(`Running with headless mode: ${headless}`);
    
    try {
        writePidFile("jobs");
    } catch (error) {
        log(`Failed to write PID file: ${error.message}`, "ERROR");
    }
    
    setupShutdownHandlers(log);
    
    let driver;
    try {
        driver = await initializeDriver(headless, log);
        await login(driver, username, password, log);
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
