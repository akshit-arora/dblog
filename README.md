# Laravel DB Log Library

A simple Laravel library to log your heavy queries bifurcated by the time taken in your application.

## Useful when
- You want to track the queries which are making your application slow.
- You want to monitor the application for the queries which is taking way longer than expected.
- You want to find the slow pages in your application

## WARNING

*The library writes the slow SQL queries log in the storage folder. Please ensure that the storage folder is not publically accessible if you've kept this library in production.*

Special thanks to the package: https://github.com/overtrue/laravel-query-logger
