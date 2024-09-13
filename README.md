# Open DMARCAnalyzer

Open DMARCAnalyzer is an open-source tool that simplifies the process of analyzing DMARC reports. By uploading ZIP files containing DMARC XML data, users can instantly generate an easy-to-read HTML report, visualize the results, and download a bundled archive containing the original XML, the generated report, and a PNG snapshot.

## Features

- **File Upload**: Upload DMARC reports in ZIP format, and the tool will handle extraction and processing.
- **DMARC Compliance Check**: Automatically checks SPF and DKIM authentication, showing "Pass" or "Fail" results for each.
- **Snapshot Generation**: Automatically generates a PNG snapshot of the analysis report for easy reference.
- **Downloadable Archive**: Downloads the original XML file, the generated HTML report, and the PNG snapshot as a ZIP file.

## Installation

To set up Open DMARCAnalyzer on your own server:

1. Clone the repository.
2. Ensure PHP 7.4+ and `php-zip` are installed on your server.
3. Install the `wkhtmltoimage` utility for generating PNG snapshots.
4. Configure your web server (Nginx or Apache) to support PHP and handle file uploads.
5. Deploy the files to your web server's document root.

## Usage

1. Navigate to the web interface.
2. Upload a ZIP file containing your DMARC XML reports.
3. View the DMARC compliance analysis on the page.
4. Download the ZIP archive containing the analysis results.

## Future Improvements

- **Security Enhancements**: Validate ZIP file uploads and sanitize inputs to prevent vulnerabilities.
- **Improved Error Handling**: Add more detailed error messages and implement server-side logging.
- **Asynchronous Processing**: Handle large files or time-consuming processes without blocking the UI.
- **Extended DMARC Metrics**: Add support for advanced DMARC metrics and alignments.
- **User Authentication**: Add user login for managing private or public access to the tool.

## License

Open DMARCAnalyzer is licensed under the GNU General Public License v3. You are free to use, modify, and distribute this software under the terms of the license.

---

### GNU GENERAL PUBLIC LICENSE Version 3

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If not, see [https://www.gnu.org/licenses/](https://www.gnu.org/licenses/).

## Contributing

Feel free to open issues and pull requests to suggest improvements or report bugs. Contributions are welcome!
