# Module Ibad PartnerInvoiceShipment

A Magento 2 module that automates the creation of shipments and invoices immediately after an order is placed, based on the presence of a specific cookie (partner).​

## Approach Overview
The module listens to the checkout_submit_all_after event to automate the creation of shipments and invoices immediately after an order is placed. If a specific cookie (partner) is set, it assigns the partner name to the order and proceeds to:​

Split the order items into chunks (e.g., two shipments if more than one item).​

For each chunk:

- Create a shipment with the respective items.​
- Create an invoice for the same items.​
- Save both using a database transaction to ensure atomicity.​

## Implemented Design Patterns
### Observer Pattern

Implementation: The OrderPlaceAfter class implements ObserverInterface and listens to the checkout_submit_all_after event.​

Purpose: Allows the module to react to order placement events without modifying core Magento code, promoting a decoupled architecture.​

### Dependency Injection (DI)

Implementation: Dependencies like LoggerInterface, ShipmentFactory, InvoiceService, ItemFactory, TransactionFactory, and CookieManagerInterface are injected via the constructor.​

Purpose: Facilitates better modularity, testability, and adherence to the inversion of control principle.​

### Factory Pattern

Implementation: Uses ShipmentFactory and ItemFactory to create instances of shipments and shipment items.​

Purpose: Encapsulates the instantiation logic, allowing for more flexible and maintainable code.​

### Service Layer Pattern

Implementation: Utilizes InvoiceService to prepare and register invoices.​

Purpose: Encapsulates business logic related to invoice processing, promoting separation of concerns.​

### Transaction Script Pattern

Implementation: Employs TransactionFactory to create a database transaction that saves the shipment, invoice, and order objects atomically.​

Purpose: Ensures data integrity by committing all related operations as a single transaction.​

### Cookie Management Abstraction

Implementation: Accesses the partner cookie using CookieManagerInterface instead of directly accessing $_COOKIE.​

Purpose: Provides a secure and testable way to handle cookies, adhering to Magento's best practices.​

## Key Decisions
- Conditional Execution Based on Cookie: The module checks for the presence of a partner cookie to determine whether to execute the shipment and invoice creation logic.​
  
- Item Chunking for Shipments: Items are divided into chunks to create multiple shipments and invoices, facilitating partial shipments.​
  
- Use of Magento's Convert\Order Class: Utilizing Convert\Order ensures that shipment and invoice items are correctly initialized with all necessary data.​

- Logging: Extensive logging is implemented to aid in debugging and monitoring the shipment and invoice creation processes.​
