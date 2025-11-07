"""
Monday CRM to URLA Integration
-------------------------------
This script handles the automation between Monday CRM and URLA PDF generation.
"""

import os
import sys
from typing import Dict, Any
import json

# Import our URLA populator
from urla_populator_overlay import URLAPopulator


class MondayIntegration:
    """Handles integration with Monday CRM."""
    
    def __init__(self, urla_template_path: str, output_directory: str):
        """
        Initialize the Monday integration.
        
        Args:
            urla_template_path: Path to the blank URLA PDF template
            output_directory: Directory where populated PDFs will be saved
        """
        self.urla_template_path = urla_template_path
        self.output_directory = output_directory
        self.populator = URLAPopulator(urla_template_path)
        
        # Ensure output directory exists
        os.makedirs(output_directory, exist_ok=True)
    
    def sanitize_filename(self, name: str) -> str:
        """
        Create a safe filename from a name.
        
        Args:
            name: The borrower's name
            
        Returns:
            Sanitized filename
        """
        # Remove special characters
        safe_name = "".join(c for c in name if c.isalnum() or c in (' ', '-', '_'))
        safe_name = safe_name.strip().replace(' ', '_')
        return safe_name
    
    def process_monday_lead(self, lead_data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Process a lead from Monday CRM and generate a URLA PDF.
        
        Args:
            lead_data: Dictionary containing all the Monday CRM fields
            
        Returns:
            Dictionary with results including PDF path and status
        """
        result = {
            'success': False,
            'pdf_path': None,
            'error': None,
            'borrower_name': lead_data.get('Name', 'Unknown')
        }
        
        try:
            # Validate required fields
            required_fields = ['Name', 'Email']
            missing_fields = [f for f in required_fields if not lead_data.get(f)]
            
            if missing_fields:
                result['error'] = f"Missing required fields: {', '.join(missing_fields)}"
                return result
            
            # Generate filename
            borrower_name = lead_data.get('Name', 'Borrower')
            safe_name = self.sanitize_filename(borrower_name)
            
            # Use email or ID to make filename unique if provided
            unique_id = lead_data.get('monday_item_id', '')
            if unique_id:
                filename = f"URLA_{safe_name}_{unique_id}.pdf"
            else:
                from datetime import datetime
                timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
                filename = f"URLA_{safe_name}_{timestamp}.pdf"
            
            output_path = os.path.join(self.output_directory, filename)
            
            # Generate the URLA PDF
            success = self.populator.create_populated_pdf(lead_data, output_path)
            
            if success:
                result['success'] = True
                result['pdf_path'] = output_path
                print(f"✓ Successfully created URLA PDF")
                print(f"  Path: {output_path}")
            else:
                result['error'] = "Failed to generate PDF"
            
        except Exception as e:
            result['error'] = str(e)
        
        return result


def main():
    """
    Main function for command-line usage.
    
    Usage:
        python monday_integration.py --data '{"Name": "John Doe", ...}'
    """
    
    # Configuration - UPDATE THESE PATHS FOR YOUR SERVER
    URLA_TEMPLATE = os.path.join(os.path.dirname(__file__), 'URLA.pdf')
    OUTPUT_DIR = os.path.join(os.path.dirname(__file__), 'outputs')
    
    # Initialize integration
    integration = MondayIntegration(URLA_TEMPLATE, OUTPUT_DIR)
    
    # Parse command line arguments
    if len(sys.argv) > 2 and sys.argv[1] == '--data':
        lead_data = json.loads(sys.argv[2])
    else:
        print("Usage: python monday_integration.py --data '{json}'")
        sys.exit(1)
    
    # Process the lead
    result = integration.process_monday_lead(lead_data)
    
    # Display results
    if result['success']:
        print(f"✓ Successfully created URLA PDF")
        print(f"  Path: {result['pdf_path']}")
        print(f"  Borrower: {result['borrower_name']}")
        sys.exit(0)
    else:
        print(f"✗ Failed to create URLA PDF")
        print(f"  Error: {result['error']}")
        sys.exit(1)


if __name__ == "__main__":
    main()
