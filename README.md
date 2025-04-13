# Module Ibad PartnerInvoiceShipment

A Magento 2 module that automates the creation of shipments and invoices immediately after an order is placed, based on the presence of a specific cookie (partner).​

## Approach Overview
The module listens to the checkout_submit_all_after event to automate the creation of shipments and invoices immediately after an order is placed. If a specific cookie (partner) is set, it assigns the partner name to the order and proceeds to:​

Split the order items into chunks (e.g., two shipments if more than one item).​

For each chunk:

- Create a shipment with the respective items.​
- Create an invoice for the same items.​
- Save both using a database transaction to ensure atomicity.​

## Architectural Layers
The module interacts with various layers of Magento 2's architecture:​

### Presentation Layer:

Handles user interactions and displays information.
In this module, the presentation layer is indirectly involved through the use of cookies set in the frontend.​

### Service Layer:

Acts as an interface between the presentation and domain layers.​
Utilizes Magento's service contracts, such as InvoiceService, to handle business operations.​

### Domain Layer:

Contains the business logic for processing orders, shipments, and invoices.​
The checkout_submit_all_after observer encapsulates this logic.​

### Persistence Layer:

Manages data storage and retrieval.​
The module interacts with Magento's models and resource models to persist data changes.​

## Key Components
### Observer:

OrderPlaceAfter observes the sales_order_place_after event to trigger shipment and invoice creation.​

### Cookie Management:

Uses CookieManagerInterface to retrieve the partner cookie value, determining if the module's functionality should be executed.​

### Shipment and Invoice Creation:

Leverages ShipmentFactory, ItemFactory, and InvoiceService to programmatically create shipments and invoices.​

### Transaction Management:

Employs TransactionFactory to ensure atomicity when saving shipments, invoices, and order updates.​


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

### Conditional Execution Based on Cookie:

The module's functionality is triggered only if the partner cookie is present, allowing for targeted processing.​

### Item Chunking for Shipments:

Order items are divided into chunks to create multiple shipments and invoices, facilitating partial shipments.​

### Use of Magento's Convert\Order Class:

Ensures that shipment and invoice items are correctly initialized with all necessary data.​

### Logging:

Extensive logging is implemented to aid in debugging and monitoring the shipment and invoice creation processes.​
