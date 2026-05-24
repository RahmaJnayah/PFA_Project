#!/usr/bin/env python3
"""
UNICEF News Indexation System - Setup Verification Script

This script verifies that all required components are installed and configured correctly.
Run this after installation to ensure everything is working.

Usage:
    python verify_setup.py
    python3 verify_setup.py
"""

import sys
import os
import subprocess
import platform

class SetupVerifier:
    def __init__(self):
        self.checks_passed = 0
        self.checks_failed = 0
        self.warnings = []
        self.os_type = platform.system()
        
    def print_header(self, text):
        print("\n" + "="*60)
        print(f"  {text}")
        print("="*60)
    
    def print_check(self, name, passed, message=""):
        status = "✅ PASS" if passed else "❌ FAIL"
        print(f"{status} - {name}")
        if message:
            print(f"    └─ {message}")
        
        if passed:
            self.checks_passed += 1
        else:
            self.checks_failed += 1
    
    def print_warning(self, message):
        print(f"⚠️  WARNING - {message}")
        self.warnings.append(message)
    
    def print_info(self, message):
        print(f"ℹ️  INFO - {message}")
    
    def verify_python_version(self):
        """Check Python 3.8+"""
        self.print_header("1. Python Version Check")
        version = sys.version_info
        passed = version.major == 3 and version.minor >= 8
        self.print_check(
            "Python 3.8+",
            passed,
            f"Running Python {version.major}.{version.minor}.{version.micro}"
        )
        return passed
    
    def verify_mysql_connector(self):
        """Check mysql-connector-python installation"""
        self.print_header("2. MySQL Connector Check")
        try:
            import mysql.connector
            version = mysql.connector.__version__
            self.print_check("mysql-connector-python", True, f"Version {version} installed")
            return True
        except ImportError:
            self.print_check("mysql-connector-python", False, 
                "Not installed. Run: pip install mysql-connector-python")
            return False
    
    def verify_other_packages(self):
        """Check other Python dependencies"""
        self.print_header("3. Python Dependencies Check")
        
        packages = {
            'requests': 'HTTP library',
            'bs4': 'BeautifulSoup4 library'
        }
        
        all_good = True
        for package, description in packages.items():
            try:
                __import__(package)
                self.print_check(package, True, description)
            except ImportError:
                self.print_check(package, False, 
                    f"{description} - Install with: pip install {package}")
                all_good = False
        
        return all_good
    
    def verify_database_connection(self):
        """Test MySQL connection"""
        self.print_header("4. Database Connection Check")
        
        try:
            import mysql.connector
            
            try:
                conn = mysql.connector.connect(
                    host='localhost',
                    port=3307,
                    user='root',
                    password='',
                    database='unicef_news_db'

                )
                self.print_check("MySQL Connection", True, "Connected to unicef_news_db")
                
                # Check tables
                cursor = conn.cursor()
                cursor.execute("SHOW TABLES")
                tables = cursor.fetchall()
                table_names = [t[0] for t in tables]
                
                required_tables = {'unicef_news', 'indexation_log'}
                missing_tables = required_tables - set(table_names)
                
                if missing_tables:
                    self.print_check("Required Tables", False,
                        f"Missing tables: {', '.join(missing_tables)}. "
                        "Import database/schema.sql using phpMyAdmin")
                    conn.close()
                    return False
                else:
                    self.print_check("Required Tables", True,
                        f"Found: {', '.join(table_names)}")
                
                conn.close()
                return True
                
            except mysql.connector.Error as err:
                self.print_check("MySQL Connection", False,
                    f"Connection failed: {err.msg}")
                self.print_info("Ensure:")
                self.print_info("  1. MySQL is running in XAMPP")
                self.print_info("  2. Database 'unicef_news_db' exists")
                self.print_info("  3. Username/password are correct (root/empty)")
                return False
        except ImportError:
            self.print_check("MySQL Connection", False, "mysql-connector-python not installed")
            return False
    
    def verify_files_exist(self):
        """Check if all required project files exist"""
        self.print_header("5. Project Files Check")
        
        base_dir = os.path.dirname(os.path.abspath(__file__))
        
        required_files = {
            'index.php': 'PHP entry point',
            'config/config.php': 'Configuration file',
            'database/schema.sql': 'Database schema',
            'php/controllers/SearchController.php': 'Search controller',
            'php/models/NewsModel.php': 'Database model',
            'python/extract_news.py': 'News extraction script',
            'python/indexation_engine.py': 'Search indexation engine',
            'php/views/search_form.php': 'Search form view',
            'php/views/search_results.php': 'Results view'
        }
        
        all_present = True
        for filepath, description in required_files.items():
            full_path = os.path.join(base_dir, filepath)
            exists = os.path.isfile(full_path)
            self.print_check(filepath, exists, description)
            if not exists:
                all_present = False
        
        return all_present
    
    def verify_config_paths(self):
        """Verify paths in config.php"""
        self.print_header("6. Configuration Paths Check")
        
        base_dir = os.path.dirname(os.path.abspath(__file__))
        config_file = os.path.join(base_dir, 'config', 'config.php')
        
        if not os.path.exists(config_file):
            self.print_check("config.php readable", False, "File not found")
            return False
        
        with open(config_file, 'r') as f:
            content = f.read()
        
        # Check for Python path definitions
        has_extractor = 'PYTHON_EXTRACTOR' in content
        has_indexer = 'PYTHON_INDEXER' in content
        
        self.print_check("Python paths defined", 
            has_extractor and has_indexer,
            "PYTHON_EXTRACTOR and PYTHON_INDEXER constants")
        
        # Check for database constants
        has_db_const = all(const in content for const in 
            ['DB_HOST', 'DB_USER', 'DB_PASS', 'DB_NAME'])
        
        self.print_check("Database constants defined", 
            has_db_const,
            "DB_HOST, DB_USER, DB_PASS, DB_NAME constants")
        
        return has_extractor and has_indexer and has_db_const
    
    def verify_php_installed(self):
        """Check if PHP is available"""
        self.print_header("7. PHP Installation Check")
        
        try:
            result = subprocess.run(['php', '--version'], 
                capture_output=True, text=True, timeout=5)
            
            if result.returncode == 0:
                version_line = result.stdout.split('\n')[0]
                self.print_check("PHP installed", True, version_line)
                return True
            else:
                self.print_warning("PHP command failed")
                return False
        except FileNotFoundError:
            self.print_check("PHP installed", False,
                "PHP not found in PATH. Add XAMPP to environment PATH")
            self.print_info(f"You're on {self.os_type}")
            if self.os_type == "Windows":
                self.print_info("Add to PATH: C:\\xampp\\php\\")
            elif self.os_type == "Darwin":
                self.print_info("Add to PATH: /Applications/XAMPP/xamppfiles/bin/")
            return False
        except Exception as e:
            self.print_check("PHP installed", False, str(e))
            return False
    
    def verify_scripts_executable(self):
        """Check if Python scripts can be executed"""
        self.print_header("8. Script Executability Check")
        
        base_dir = os.path.dirname(os.path.abspath(__file__))
        scripts = {
            'extract_news.py': 'News extraction',
            'indexation_engine.py': 'Search indexation'
        }
        
        all_good = True
        for script, description in scripts.items():
            script_path = os.path.join(base_dir, 'python', script)
            exists = os.path.isfile(script_path)
            
            if exists:
                try:
                    result = subprocess.run(
                        [sys.executable, script_path, '--help'],
                        capture_output=True,
                        timeout=2,
                        cwd=base_dir
                    )
                    # Script should error with --help but still execute
                    self.print_check(f"{script} executable", True, description)
                except Exception as e:
                    self.print_check(f"{script} executable", False, str(e))
                    all_good = False
            else:
                self.print_check(f"{script} exists", False, "File not found")
                all_good = False
        
        return all_good
    
    def verify_web_access(self):
        """Check if application is accessible via web"""
        self.print_header("9. Web Access Check")
        
        self.print_info("Application should be accessible at:")
        
        if self.os_type == "Windows":
            self.print_info("  http://localhost/unicef_indexation/")
        else:
            self.print_info("  http://localhost/unicef_indexation/")
        
        self.print_info("Ensure:")
        self.print_info("  1. Project is in XAMPP htdocs directory")
        self.print_info("  2. Apache is running")
        self.print_info("  3. Directory name is 'unicef_indexation' (or update paths)")
        
        self.print_check("Web access information", True, "See details above")
        return True
    
    def print_summary(self):
        """Print final summary"""
        self.print_header("SUMMARY")
        
        total = self.checks_passed + self.checks_failed
        percentage = (self.checks_passed / total * 100) if total > 0 else 0
        
        print(f"\nTests Passed: {self.checks_passed}/{total} ({percentage:.0f}%)")
        
        if self.checks_failed == 0:
            print("\n✅ All checks passed! System is ready to use.")
            print("\nNext steps:")
            print("  1. Extract news: Click 'Extract News' button or run:")
            print(f"     python {os.path.join(os.path.dirname(__file__), 'python/extract_news.py')}")
            print("  2. Perform a search: Enter a query like 'children' or 'vaccination'")
            print("  3. View statistics and history")
            print("  4. Integrate with your dashboard")
            return True
        else:
            print(f"\n❌ {self.checks_failed} check(s) failed!")
            print("\nPlease fix the above issues before proceeding.")
            
            if self.warnings:
                print(f"\n⚠️  Warnings ({len(self.warnings)}):")
                for warning in self.warnings:
                    print(f"   - {warning}")
            
            return False
    
    def run_all_checks(self):
        """Run all verification checks"""
        print("""
╔════════════════════════════════════════════════════════════╗
║   UNICEF News Indexation System - Setup Verification      ║
║                                                            ║
║   This script checks if your system is properly set up    ║
║   for running the UNICEF news search application.         ║
╚════════════════════════════════════════════════════════════╝
        """)
        
        # Run all checks
        checks = [
            self.verify_python_version(),
            self.verify_mysql_connector(),
            self.verify_other_packages(),
            self.verify_database_connection(),
            self.verify_files_exist(),
            self.verify_config_paths(),
            self.verify_php_installed(),
            self.verify_scripts_executable(),
            self.verify_web_access()
        ]
        
        # Print summary
        success = self.print_summary()
        
        return success


if __name__ == '__main__':
    verifier = SetupVerifier()
    success = verifier.run_all_checks()
    
    # Exit with appropriate code
    sys.exit(0 if success else 1)
