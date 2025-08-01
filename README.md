
# Modern Tech Solutions - HR Management System

Welcome to the **Modern Tech Solutions HR Management System**, a comprehensive web application designed to streamline Human Resources operations. Built using PHP and MySQL, this system provides essential features like employee management, payroll processing, leave requests, attendance tracking, and more — all in a modern, responsive interface.

---

## Table of Contents

- [Features](#features)  
- [Technologies Used](#technologies-used)  
- [Installation](#installation)  
- [Usage](#usage)  
- [Folder Structure](#folder-structure)  
- [Contributing](#contributing)  
- [License](#license)  
- [Contact](#contact)  

---

## Features

- User authentication with session management  
- Employee CRUD (Create, Read, Update, Delete) operations  
- Payroll management and PDF payslip generation  
- Leave requests and approvals  
- Attendance tracking  
- Responsive, Bootstrap-based UI for a modern look  
- Secure password handling and form validation  
- Dashboard with summary statistics and charts  

---

## Technologies Used

- PHP 7.x / 8.x  
- MySQL / MariaDB  
- Bootstrap 5  
- JavaScript (for UI interactivity)  
- Dompdf (for PDF generation)  
- HTML5 & CSS3  

---

## Installation

1. **Clone the repository**  
   ```bash
   git clone https://github.com/yourusername/modern-tech-hr-system.git
   ```

2. **Set up the database**  
   - Create a new MySQL database (e.g., `modern_tech_hr`)  
   - Import the `database.sql` file included in the repo to create tables and sample data  

3. **Configure database connection**  
   - Open `includes/db.php`  
   - Update the database host, username, password, and database name to match your environment  

4. **Set up your web server**  
   - Place the project files in your web root (e.g., `htdocs` or `www` folder)  
   - Make sure PHP is enabled and your web server is running  

5. **Access the application**  
   - Open your browser and go to `http://localhost/modern-tech-hr-system` or your deployed URL
  
   ** Make sure to extract the dompdf inside pdf folder (For payslip generation to work) **

---

## Usage

- Login with admin credentials (`username: Admin`, `password: admin123`) or your configured user  
- Navigate the dashboard to view summaries and manage employees  
- Use the sidebar to access payroll, attendance, leave requests, and reports  
- Add, edit, or remove employees as needed  
- Generate payslips in PDF format for payroll processing  

---

## Folder Structure

```
/modern-tech-hr-system
│
├── /auth/              # Authentication pages (login, logout)
├── /includes/          # Config files, database connection, header/footer templates
├── /employees/         # Employee management modules
├── /payroll/           # Payroll and PDF generation features
├── /leave/             # Leave request management
├── /attendance/        # Attendance tracking management
├── /assets/            # CSS, JS and Images
├── dashboard.php           # Main dashboard page
├── database.sql        # Database schema and sample data
└── README.md           # This readme file
```

---

## Contributing

Contributions, issues, and feature requests are welcome!  
Feel free to fork the repository and submit pull requests.  

---

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

---

## Contact

For support or inquiries, contact:  
**Mogamat Smith**  
Email: suhairsmith17@gmail.com  
GitHub: [Kxngzero329](https://github.com/kxngzero329)  

---

Thank you for using **Modern Tech Solutions HR System** — your all-in-one HR management solution!
