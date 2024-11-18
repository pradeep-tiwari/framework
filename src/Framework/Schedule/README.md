# Cron Expression Parser

The Lightpack Framework includes a powerful and flexible Cron expression parser that allows you to schedule tasks using standard cron syntax. This component is part of the `Lightpack\Schedule` namespace and provides an intuitive interface for working with cron expressions.

## Table of Contents
- [Basic Usage](#basic-usage)
- [Cron Expression Format](#cron-expression-format)
- [Special Time Strings](#special-time-strings)
- [Alternative Weekday Format](#alternative-weekday-format)
- [Methods](#methods)
- [Examples](#examples)
- [Best Practices](#best-practices)

## Basic Usage

```php
use Lightpack\Schedule\Cron;

// Create a new Cron instance
$cron = new Cron('* * * * *');

// Check if the cron is due to run
$now = new DateTime();
if ($cron->isDue($now)) {
    // Execute your scheduled task
}

// Get the next due time
$nextDueTime = $cron->nextDueAt($now);

// Get the previous due time
$previousDueTime = $cron->previousDueAt($now);
```

## Cron Expression Format

The cron expression consists of five fields separated by spaces:

```
┌───────────── minute (0 - 59)
│ ┌───────────── hour (0 - 23)
│ │ ┌───────────── day of month (1 - 31)
│ │ │ ┌───────────── month (1 - 12)
│ │ │ │ ┌───────────── day of week (0 - 6) (Sunday to Saturday)
│ │ │ │ │
* * * * *
```

Each field can contain:
- `*` - any value
- `,` - value list separator
- `-` - range of values
- `/` - step values
- Numbers - exact values

## Special Time Strings

For common scheduling patterns, you can use these special time strings:

```php
$cron = new Cron('@yearly');   // Run once a year at midnight on January 1st (0 0 1 1 *)
$cron = new Cron('@monthly');  // Run once a month at midnight on the first (0 0 1 * *)
$cron = new Cron('@weekly');   // Run once a week at midnight on Sunday (0 0 * * 0)
$cron = new Cron('@daily');    // Run once a day at midnight (0 0 * * *)
$cron = new Cron('@hourly');   // Run once an hour at the start of the hour (0 * * * *)
```

## Alternative Weekday Format

By default, the day of week is numbered from 0 (Sunday) to 6 (Saturday). You can opt to use the alternative format where Sunday is 7:

```php
$cron = new Cron('* * * * 7');  // Run every minute on Sunday
$cron->useAlternativeWeekdays();
```

## Methods

### isDue()
Check if the cron should run at a specific time:
```php
if ($cron->isDue(new DateTime())) {
    // Run the task
}
```

### nextDueAt()
Get the next time the cron should run:
```php
$nextRun = $cron->nextDueAt(new DateTime());
echo $nextRun->format('Y-m-d H:i:s');
```

### previousDueAt()
Get the previous time the cron should have run:
```php
$lastRun = $cron->previousDueAt(new DateTime());
echo $lastRun->format('Y-m-d H:i:s');
```

## Examples

### Every Five Minutes
```php
$cron = new Cron('*/5 * * * *');
```

### Every Weekday at 8 AM
```php
$cron = new Cron('0 8 * * 1-5');
```

### First Monday of Every Month
```php
$cron = new Cron('0 0 1-7 * 1');
```

### Multiple Times Per Hour
```php
$cron = new Cron('0,15,30,45 * * * *');  // Every quarter hour
```

### Using Ranges with Steps
```php
$cron = new Cron('*/15 9-17 * * 1-5');  // Every 15 minutes during business hours
```

## Best Practices

1. **Validate Expressions**
   Always validate cron expressions before using them in production. Invalid expressions will throw an exception.

2. **Time Zone Awareness**
   Be mindful of the timezone of your DateTime objects. The cron parser uses the timezone of the provided DateTime instance.

3. **Performance Considerations**
   - The parser has a maximum iteration limit of 1440 (minutes in a day) when calculating next/previous due times
   - Avoid overly complex expressions that might require many iterations
   - Consider using special time strings for common patterns

4. **Error Handling**
   ```php
   try {
       $cron = new Cron('invalid expression');
   } catch (Exception $e) {
       // Handle invalid expression
   }

   try {
       $nextRun = $cron->nextDueAt($dateTime);
   } catch (Exception $e) {
       // Handle case where next run couldn't be determined
   }
   ```

## Contributing

The Cron parser is part of the Lightpack Framework. If you find bugs or want to contribute improvements, please visit our GitHub repository.

## License

This component is part of the Lightpack Framework and is released under the MIT License.
