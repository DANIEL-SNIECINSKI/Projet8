import time
from selenium import webdriver
from selenium.webdriver.common.keys import Keys
browser = webdriver.Chrome(executable_path="/usr/bin/chromedriver")
browser.get('http://trendoceans.com')
print('Title: %s' % browser.title)
time.sleep(10)
browser.quit()
