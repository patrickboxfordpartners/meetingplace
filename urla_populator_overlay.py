"""
URLA PDF Populator - Overlay Method
------------------------------------
This script creates a PDF overlay with Monday CRM data and merges it with
the URLA template.
"""

from reportlab.pdfgen import canvas
from reportlab.lib.pagesizes import letter
from pypdf import PdfReader, PdfWriter
from io import BytesIO
import re
from datetime import datetime
from typing import Dict, Any, Tuple


class AddressParser:
    """Parse address strings into components."""
    
    @staticmethod
    def parse_address(address_string: str) -> Dict[str, str]:
        """Parse full address into components."""
        if not address_string or address_string.strip() == "":
            return {"street": "", "unit": "", "city": "", "state": "", "zip": "", "country": "USA"}
        
        parts = [p.strip() for p in address_string.split(',')]
        result = {"street": "", "unit": "", "city": "", "state": "", "zip": "", "country": "USA"}
        
        if len(parts) >= 1:
            # Check for unit number in street address
            street_parts = parts[0].split('#')
            result["street"] = street_parts[0].strip()
            if len(street_parts) > 1:
                result["unit"] = street_parts[1].strip()
        
        if len(parts) >= 2:
            result["city"] = parts[1]
        
        if len(parts) >= 3:
            state_zip = parts[2].strip().split()
            if len(state_zip) >= 1:
                result["state"] = state_zip[0]
            if len(state_zip) >= 2:
                result["zip"] = state_zip[1]
        
        return result


class NameParser:
    """Parse name strings into components."""
    
    @staticmethod
    def parse_name(full_name: str) -> Dict[str, str]:
        """Parse full name into first, middle, last, suffix."""
        if not full_name or full_name.strip() == "":
            return {"first": "", "middle": "", "last": "", "suffix": ""}
        
        suffixes = ["Jr", "Jr.", "Sr", "Sr.", "II", "III", "IV", "V"]
        parts = full_name.strip().split()
        result = {"first": "", "middle": "", "last": "", "suffix": ""}
        
        if parts and parts[-1] in suffixes:
            result["suffix"] = parts[-1]
            parts = parts[:-1]
        
        if len(parts) >= 1:
            result["first"] = parts[0]
        if len(parts) >= 2:
            result["last"] = parts[-1]
        if len(parts) >= 3:
            result["middle"] = " ".join(parts[1:-1])
        
        return result


class PhoneParser:
    """Parse phone numbers."""
    
    @staticmethod
    def parse_phone(phone: str) -> Tuple[str, str]:
        """Parse phone into area code and number."""
        if not phone:
            return ("", "")
        
        digits = re.sub(r'\D', '', phone)
        
        if len(digits) == 10:
            return (f"({digits[:3]})", f"{digits[3:6]}-{digits[6:]}")
        elif len(digits) == 11 and digits[0] == '1':
            return (f"({digits[1:4]})", f"{digits[4:7]}-{digits[7:]}")
        
        return ("", phone)


class DateFormatter:
    """Format dates for URLA."""
    
    @staticmethod
    def format_date(date_str: str) -> str:
        """Convert date to MM/DD/YYYY format."""
        if not date_str:
            return ""
        
        for fmt in ["%m/%d/%Y", "%Y-%m-%d", "%m-%d-%Y", "%d/%m/%Y"]:
            try:
                dt = datetime.strptime(str(date_str), fmt)
                return dt.strftime("%m/%d/%Y")
            except ValueError:
                continue
        
        return str(date_str)


class TimeParser:
    """Parse time duration strings."""
    
    @staticmethod
    def parse_duration(time_str: str) -> Tuple[str, str]:
        """Parse duration into years and months."""
        if not time_str:
            return ("", "")
        
        years = 0
        months = 0
        
        year_match = re.search(r'(\d+)\s*(?:year|yr)', time_str, re.IGNORECASE)
        if year_match:
            years = int(year_match.group(1))
        
        month_match = re.search(r'(\d+)\s*(?:month|mo)', time_str, re.IGNORECASE)
        if month_match:
            months = int(month_match.group(1))
        
        return (str(years) if years else "", str(months) if months else "")


class URLAOverlayCreator:
    """
    Creates a PDF overlay with Monday CRM data positioned to match URLA form fields.
    """
    
    # Font size for different field types
    FONT_SIZE_NORMAL = 9
    FONT_SIZE_SMALL = 8
    FONT_SIZE_LARGE = 10
    
    def __init__(self):
        """Initialize the overlay creator."""
        self.width, self.height = letter
        
    def create_overlay(self, monday_data: Dict[str, Any]) -> BytesIO:
        """
        Create a PDF overlay with the data from Monday CRM.
        
        Args:
            monday_data: Dictionary containing Monday CRM field data
            
        Returns:
            BytesIO object containing the overlay PDF
        """
        # Create a BytesIO buffer for the overlay PDF
        buffer = BytesIO()
        c = canvas.Canvas(buffer, pagesize=letter)
        
        # Parse the input data
        name = NameParser.parse_name(monday_data.get('Name', ''))
        current_addr = AddressParser.parse_address(monday_data.get('Current Address', ''))
        property_addr = AddressParser.parse_address(monday_data.get('Property Address', ''))
        phone_area, phone_num = PhoneParser.parse_phone(monday_data.get('Phone', ''))
        dob = DateFormatter.format_date(monday_data.get('Date of Birth', ''))
        years_job, months_job = TimeParser.parse_duration(monday_data.get('Time At Job', ''))
        
        # PAGE 1 - Section 1: Borrower Information
        c.setFont("Helvetica", self.FONT_SIZE_NORMAL)
        
        # Section 1a: Personal Information
        # Name fields (adjust these coordinates as needed)
        self._draw_text(c, name['first'], 72, 730)
        self._draw_text(c, name['middle'], 200, 730)
        self._draw_text(c, name['last'], 320, 730)
        self._draw_text(c, name['suffix'], 480, 730)
        
        # Date of Birth
        self._draw_text(c, dob, 72, 710)
        
        # Contact Information
        self._draw_text(c, phone_area, 72, 670)
        self._draw_text(c, phone_num, 115, 670)
        self._draw_text(c, monday_data.get('Email', ''), 300, 670)
        
        # Current Address
        self._draw_text(c, current_addr['street'], 72, 640)
        self._draw_text(c, current_addr['unit'], 380, 640)
        self._draw_text(c, current_addr['city'], 72, 620)
        self._draw_text(c, current_addr['state'], 320, 620)
        self._draw_text(c, current_addr['zip'], 380, 620)
        
        # Section 1b: Current Employment
        employer = monday_data.get('Employer Name', '')
        position = monday_data.get('Job Title', '')
        base_income = monday_data.get('Monthly Gross Income', '')
        
        self._draw_text(c, employer, 72, 540)
        self._draw_text(c, position, 72, 510)
        self._draw_text(c, years_job, 72, 490)
        self._draw_text(c, months_job, 120, 490)
        self._draw_text(c, years_job, 250, 490)
        self._draw_text(c, months_job, 298, 490)
        
        # Income
        self._draw_text(c, base_income, 480, 540)
        self._draw_text(c, base_income, 480, 480)
        
        # PAGE 3 - Section 2: Financial Information
        c.showPage()
        c.showPage()
        c.setFont("Helvetica", self.FONT_SIZE_NORMAL)
        
        # Section 2a: Assets
        bank_name = monday_data.get('Bank Name', '')
        savings = monday_data.get('Approximate Savings', '')
        
        self._draw_text(c, bank_name, 180, 710)
        self._draw_text(c, savings, 480, 710)
        self._draw_text(c, savings, 480, 650)
        
        # Section 2c: Liabilities
        monthly_debt = monday_data.get('Monthly Debt Obligations', '')
        if monthly_debt:
            self._draw_text(c, monthly_debt, 480, 570)
        
        # PAGE 5 - Section 4: Loan and Property Information
        c.showPage()
        c.showPage()
        c.setFont("Helvetica", self.FONT_SIZE_NORMAL)
        
        # Loan Amount
        loan_amount = monday_data.get('Loan Amount Requested', '')
        self._draw_text(c, loan_amount, 72, 745)
        
        # Property Address
        self._draw_text(c, property_addr['street'], 72, 725)
        self._draw_text(c, property_addr['unit'], 380, 725)
        self._draw_text(c, property_addr['city'], 72, 705)
        self._draw_text(c, property_addr['state'], 320, 705)
        self._draw_text(c, property_addr['zip'], 380, 705)
        
        # Property Value
        purchase_price = monday_data.get('Purchase Price', '')
        self._draw_text(c, purchase_price, 380, 685)
        
        c.save()
        buffer.seek(0)
        return buffer
    
    def _draw_text(self, canvas_obj, text: str, x: float, y: float):
        """Helper method to draw text at specified coordinates."""
        if text:
            canvas_obj.drawString(x, y, str(text))


class URLAPopulator:
    """Main class for populating URLA PDFs."""
    
    def __init__(self, template_path: str):
        """
        Initialize with URLA template path.
        
        Args:
            template_path: Path to the blank URLA PDF template
        """
        self.template_path = template_path
        self.overlay_creator = URLAOverlayCreator()
    
    def create_populated_pdf(self, monday_data: Dict[str, Any], output_path: str) -> bool:
        """
        Create a populated URLA PDF by overlaying data on the template.
        
        Args:
            monday_data: Dictionary of data from Monday CRM
            output_path: Path where the populated PDF should be saved
            
        Returns:
            True if successful, False otherwise
        """
        try:
            # Create the overlay with Monday data
            overlay_buffer = self.overlay_creator.create_overlay(monday_data)
            
            # Read the template and overlay
            template = PdfReader(self.template_path)
            overlay = PdfReader(overlay_buffer)
            
            # Create output PDF
            writer = PdfWriter()
            
            # Merge each page
            for i, page in enumerate(template.pages):
                if i < len(overlay.pages):
                    # Merge overlay onto template page
                    page.merge_page(overlay.pages[i])
                writer.add_page(page)
            
            # Write output
            with open(output_path, 'wb') as output_file:
                writer.write(output_file)
            
            return True
            
        except Exception as e:
            print(f"Error creating populated PDF: {e}")
            import traceback
            traceback.print_exc()
            return False

## requirements.txt
pypdf
reportlab
