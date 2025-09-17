# TMS MySQL Migration Guide

This guide will help you migrate your Teacher Management System from SQLite to MySQL.

## Prerequisites

1. **XAMPP** with MySQL service running
2. **phpMyAdmin** or MySQL command line access
3. Existing TMS SQLite database

## Step 1: MySQL Database Setup

### Option A: Using phpMyAdmin
1. Open phpMyAdmin (usually at `http://localhost/phpmyadmin`)
2. Create a new database named `tms_database`
3. Import the schema by running the SQL from `schema.sql`

### Option B: Using MySQL Command Line
```sql
CREATE DATABASE tms_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tms_database;
SOURCE schema.sql;
```

## Step 2: Configure Database Connection

1. Open `config.php`
2. Update the database credentials if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'tms_database');
   define('DB_USER', 'root');        // Your MySQL username
   define('DB_PASS', '');            // Your MySQL password
   ```

## Step 3: Run Data Migration

1. Navigate to `http://localhost/your-project-path/migrate_data.php`
2. The script will automatically:
   - Connect to both SQLite and MySQL databases
   - Create MySQL tables if they don't exist
   - Migrate all existing data
   - Show migration progress and results

## Step 4: Verify Migration

1. Check the migration results on the migration page
2. Visit `http://localhost/your-project-path/db_viewer.php` to view MySQL data
3. Test the application at `http://localhost/your-project-path/dashboard.html`

## File Changes Made

### New Files:
- `config.php` - MySQL database configuration
- `schema.sql` - MySQL database schema
- `migrate_data.php` - Data migration script
- `README_MYSQL_MIGRATION.md` - This file

### Updated Files:
- `save_teacher.php` - Converted to PDO MySQL
- `fetch_teachers.php` - Converted to PDO MySQL  
- `register_teacher.php` - Converted to PDO MySQL
- `update_teacher.php` - Converted to PDO MySQL
- `delete_teacher.php` - Converted to PDO MySQL
- `get_teacher.php` - Converted to PDO MySQL
- `db_viewer.php` - Converted to MySQL viewer

## Key Improvements

### Database Structure:
- **Auto-incrementing primary keys** (INT AUTO_INCREMENT)
- **Proper foreign key constraints** with CASCADE delete
- **ENUM type** for education levels (bachelor, master, doctoral)
- **DECIMAL type** for precise IPCRF ratings
- **Indexes** on frequently queried fields
- **Timestamps** for created_at and updated_at

### Data Integrity:
- **Foreign key constraints** ensure referential integrity
- **Prepared statements** prevent SQL injection
- **Transaction support** for complex operations
- **Error handling** with proper rollbacks

### Performance:
- **Indexes** on school_year, position, department, grade_level
- **UTF8MB4** character set for full Unicode support
- **InnoDB engine** for ACID compliance and performance

## Troubleshooting

### Common Issues:

1. **Connection failed:**
   - Check MySQL service is running
   - Verify credentials in `config.php`
   - Ensure database exists

2. **Migration errors:**
   - Check SQLite database exists and is readable
   - Verify MySQL user has CREATE/INSERT permissions
   - Check error messages in migration script

3. **Data not showing:**
   - Clear browser cache
   - Check browser console for JavaScript errors
   - Verify data was migrated successfully

### Testing Steps:

1. **Add new teacher** - Test registration form
2. **View teachers** - Check dashboard displays data
3. **Edit teacher** - Test update functionality  
4. **Delete teacher** - Test delete functionality
5. **Search/filter** - Test filtering options

## Rollback Plan

If you need to rollback to SQLite:
1. Keep backup of original files
2. Restore original PHP files
3. Ensure `database.sqlite` file is intact

## Support

For issues:
1. Check browser console for errors
2. Check PHP error logs
3. Verify MySQL connection and permissions
4. Test individual PHP files directly

## Performance Notes

MySQL will provide better performance for:
- **Concurrent users** - Better handling of multiple simultaneous connections
- **Large datasets** - More efficient querying and indexing
- **Complex queries** - Advanced SQL features and optimization
- **Data integrity** - Foreign key constraints and transactions
- **Backup/recovery** - Enterprise-grade backup tools
- **Scalability** - Can handle growth in data and users

The migration maintains all existing functionality while providing a more robust and scalable database foundation.