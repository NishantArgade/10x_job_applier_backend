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
const DEFAULT_MAX_APPLICATIONS = 5;
const DEFAULT_RESUME_HEADLINE =
    "Full Stack Developer | React, Node.js | Open to Remote | Immediate Joiner";

// Load environment variables
dotenv.config({ path: path.join(__dirname, ".env") });

// Setup logging
const logDir = path.join(__dirname, "logs");
if (!fs.existsSync(logDir)) {
    fs.mkdirSync(logDir, { recursive: true });
}

const logFile = path.join(
    logDir,
    `naukri-bot-${new Date().toISOString().split("T")[0]}.log`
);

function log(message, level = "INFO") {
    const timestamp = new Date().toISOString();
    const logPrefix = `[${timestamp}] [${level}]`;
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
}

// Initialize WebDriver
async function initializeDriver(headless = false) {
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
        
        if (error.message.includes("ChromeDriver only supports Chrome version")) {
            log("There is a version mismatch between ChromeDriver and your Chrome browser.", "ERROR");
            log("Please install the correct version of ChromeDriver that matches your Chrome version.", "ERROR");
            log("Run: npm install chromedriver@<version-number> --save", "ERROR");
        }
        
        throw error;
    }
}

// Login to Naukri
async function login(driver, username, password) {
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

// Update resume headline
async function updateResumeHeadline(
    driver,
    headline = DEFAULT_RESUME_HEADLINE
) {
    log("Updating resume headline");

    await driver.get(NAUKRI_PROFILE_URL);

    try {
        // Wait for and click edit button
        await driver.wait(
            until.elementLocated(By.css("#lazyResumeHead .edit.icon")),
            10000
        );
        await driver.sleep(1000);

        const editBtn = await driver.findElement(
            By.css("#lazyResumeHead .edit.icon")
        );
        await scrollAndClick(driver, editBtn);

        // Update headline text
        await driver.wait(
            until.elementLocated(By.id("resumeHeadlineTxt")),
            10000
        );
        const headlineInput = await driver.findElement(
            By.id("resumeHeadlineTxt")
        );
        await headlineInput.clear();
        await headlineInput.sendKeys(headline);

        // Save changes
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

// Apply for a single job
async function applyForJob(driver, jobElement, jobIndex, totalJobs) {
    log(`Processing job ${jobIndex + 1}/${totalJobs}`);

    try {
        // Click on job
        await scrollAndClick(driver, jobElement);
        await driver.sleep(2000);

        // Switch to job details tab if opened
        const tabSwitched = await switchToTab(driver);

        // Check if chatbot container exists (skip if found)
        try {
            const chatbotExists = await driver.findElements(
                By.id("_mtdn7v2eyChatbotContainer")
            );
            if (chatbotExists.length > 0) {
                log(
                    `Skipping job ${jobIndex + 1}: Contains chatbot container`,
                    "WARN"
                );
                if (tabSwitched) {
                    await closeTabAndSwitchToMain(driver);
                }
                return false;
            }
        } catch (e) {
            // Chatbot not found, continue with application
        }

        // Check if this is an external job (skip if it's external)
        try {
            const externalLinks = await driver.findElements(
                By.xpath(
                    "//a[contains(text(), 'Apply on company website') or contains(@class, 'external-apply')]"
                )
            );
            if (externalLinks.length > 0) {
                log(
                    `Skipping job ${jobIndex + 1}: External job application`,
                    "WARN"
                );
                if (tabSwitched) {
                    await closeTabAndSwitchToMain(driver);
                }
                return false;
            }
        } catch (e) {
            // Not an external job, continue
        }

        // Try to find apply button
        try {
            const applyBtn = await driver.wait(
                until.elementLocated(
                    By.xpath("//button[contains(text(), 'Apply')]")
                ),
                5000
            );

            await driver.wait(until.elementIsVisible(applyBtn), 3000);
            await scrollAndClick(driver, applyBtn);
            log(`Applied to job ${jobIndex + 1}`);

            // Check for chatbot container after applying (indicates already applied or special application)
            await driver.sleep(2000);
            try {
                const postApplyChatbot = await driver.findElements(
                    By.id("_mtdn7v2eyChatbotContainer")
                );
                if (postApplyChatbot.length > 0) {
                    log(
                        `Found chatbot container after applying to job ${
                            jobIndex + 1
                        }`,
                        "WARN"
                    );
                    // Try to close it if there's a close button
                    try {
                        const closeButtons = await driver.findElements(
                            By.css("[aria-label='close']")
                        );
                        if (closeButtons.length > 0) {
                            await driver.executeScript(
                                "arguments[0].click();",
                                closeButtons[0]
                            );
                        }
                    } catch (chatbotError) {
                        // Ignore chatbot close errors
                    }
                }
            } catch (chatbotCheckError) {
                // Ignore errors when checking for chatbot
            }
        } catch (error) {
            log(
                `No Apply button found for job ${jobIndex + 1}, skipping`,
                "WARN"
            );
            if (tabSwitched) {
                await closeTabAndSwitchToMain(driver);
            }
            return false;
        }

        // Close tab if we opened one
        if (tabSwitched) {
            await closeTabAndSwitchToMain(driver);
        }

        return true;
    } catch (error) {
        log(`Failed to apply to job ${jobIndex + 1}: ${error.message}`, "WARN");

        // Close any tabs that might have opened
        try {
            await closeTabAndSwitchToMain(driver);
        } catch (e) {
            log(`Error closing tab: ${e.message}`, "WARN");
        }

        return false;
    }
}

// Apply for multiple jobs
async function applyRecommendedJobs(driver) {
    log("Navigating to recommended jobs page");
    await driver.get(NAUKRI_JOBS_URL);
    await driver.sleep(5000);

    // Get job listings
    try {
        log("Loading job listings");
        await driver.wait(
            until.elementLocated(By.css("article.jobTuple")),
            15000
        );
        const jobArticles = await driver.findElements(
            By.css("article.jobTuple")
        );

        const jobsToApply = jobArticles.length; // Apply to all jobs
        log(`Found ${jobArticles.length} jobs, will apply to all of them`);

        let appliedCount = 0; // Apply for jobs
        for (let i = 0; i < jobsToApply; i++) {
            // Re-fetch elements to avoid stale references
            const refreshedJobArticles = await driver.findElements(
                By.css("article.jobTuple")
            );

            if (i >= refreshedJobArticles.length) {
                log("No more jobs available", "WARN");
                break;
            }

            // Try to identify easy jobs (skip jobs that look complex or require assessment)
            try {
                const jobItem = refreshedJobArticles[i];
                const jobTitle = await jobItem
                    .findElement(By.css(".title"))
                    .getText()
                    .catch(() => "");
                const jobDescription = await jobItem
                    .findElement(By.css(".job-description"))
                    .getText()
                    .catch(() => "");

                // Skip jobs with certain keywords that suggest complexity or assessment
                const complexJobTerms = [
                    "senior",
                    "lead",
                    "architect",
                    "manager",
                ];
                const isComplexJob = complexJobTerms.some(
                    (term) =>
                        jobTitle.toLowerCase().includes(term) ||
                        jobDescription.toLowerCase().includes(term)
                );

                if (isComplexJob) {
                    log(
                        `Skipping job ${
                            i + 1
                        }: Not an easy job based on title/description`,
                        "WARN"
                    );
                    continue;
                }
            } catch (jobCheckError) {
                // If we can't check job complexity, continue with the application
            }

            const success = await applyForJob(
                driver,
                refreshedJobArticles[i],
                i,
                jobsToApply
            );
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

// Main bot function
export async function naukriBot() {
    log("Starting Naukri automation bot");    // Get configuration from environment variables
    const config = {
        username: process.env.NAUKRI_USERNAME,
        password: process.env.NAUKRI_PASSWORD,
        jobTitle: process.env.NAUKRI_JOB_TITLE || DEFAULT_JOB_TITLE,
        location: process.env.NAUKRI_LOCATION || "",
        headless: process.env.NAUKRI_HEADLESS === "true",
    };

    if (!config.username || !config.password) {
        log("Naukri credentials not found in environment variables", "ERROR");
        throw new Error("Missing Naukri credentials");
    }

    log(
        `Configuration: Job Title: ${config.jobTitle}, Headless: ${config.headless}, No application limit`
    );

    let driver;    try {
        driver = await initializeDriver(config.headless);
        await login(driver, config.username, config.password);
        await updateResumeHeadline(driver);
        await applyRecommendedJobs(driver);
        log("Naukri automation completed successfully");
    } catch (error) {
        log(`Error in Naukri automation: ${error.message}`, "ERROR");
        throw error;
    } finally {
        if (driver) {
            log("Closing browser");
            await driver.quit();
        }
    }
}

naukriBot()
    .then(() => log("Naukri bot completed successfully"))
    .catch((error) => log(`Naukri bot failed: ${error.message}`, "ERROR"));
