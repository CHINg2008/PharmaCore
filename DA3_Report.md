# PharmaCore: Pharmacy Management System
**DA3 Assignment Report: Graph Database Integration (Neo4j)**

---

## 1. Project Explanation
**PharmaCore** is a comprehensive, web-based Pharmacy Management System built using the Laravel framework. It is designed to handle the core operations of a modern pharmacy, tracking the entire supply chain lifecycle from pharmaceutical suppliers to retail sales. 

### Key Features:
- **Inventory Management:** Tracking individual medicine batches, monitoring stock levels, and providing automated alerts for low stock (Out of Stock) and Expired medications.
- **Supplier & Category Management:** Organizing medicines by pharmacological categories and managing relationships with pharmaceutical manufacturers/suppliers.
- **Point of Sale (POS):** Recording daily retail transactions and generating revenue reports.
- **Advanced Graph Analytics:** A dedicated analytics dashboard that maps the complex relationships between suppliers, categories, inventory, and sales using Graph Database principles.

---

## 2. Justification for Choosing Neo4j (Graph Database)
While a traditional Relational Database (like SQLite/MySQL) is excellent for strict transactional data (like recording a sale), it struggles with deeply connected data, such as tracking supply chain provenance or finding hidden patterns in sales data.

**Neo4j was chosen to extend this project because:**
1. **Relationship-First Architecture:** In a pharmacy, relationships are just as important as the data itself. Knowing *which* supplier provided a specific batch of medicine that is now expiring is a multi-hop query that is slow in SQL but instantaneous in a Graph database.
2. **Traceability & Provenance:** If a batch of medicine is recalled by the FDA, a Graph Database can instantly trace the path from the `[Supplier]` -> `[Product Batch]` -> `[Sale]` -> `[Customer]` without writing complex `JOIN` statements.
3. **Analytics Visualization:** Neo4j allows us to project the entire pharmacy ecosystem visually. By modeling the data as a graph, administrators can literally *see* which suppliers are the central hubs of their business.

---

## 3. Graph Schema Design
The data is modeled in the graph using the following schema topology:

### Nodes (Entities)
- `(:Category {id, name})` - e.g., Analgesics, Antibiotics
- `(:Supplier {id, name, company, email, phone})` - e.g., Pfizer, Johnson & Johnson
- `(:Product {id, name, price, quantity, expiry_date})` - The physical batch of medicine
- `(:Sale {id, quantity, total_price, date})` - The retail transaction

### Edges (Relationships)
- `(:Supplier)-[:SUPPLIES]->(:Product)`
- `(:Product)-[:BELONGS_TO]->(:Category)`
- `(:Sale)-[:SOLD_ITEM]->(:Product)`

---

## 4. Graph Operations (Cypher Queries)
Here are the core operations demonstrating how the system interacts with the Graph Data Model:

**Create Operations (Adding new inventory):**
```cypher
// Add a new product and link it to a supplier and category
MERGE (s:Supplier {name: 'PharmaInc'})
MERGE (c:Category {name: 'Antihistamine'})
CREATE (p:Product {name: 'Cetirizine 10mg', quantity: 100})
CREATE (s)-[:SUPPLIES]->(p)
CREATE (p)-[:BELONGS_TO]->(c)
```

**Read Operations (Analytics & Reporting):**
```cypher
// Find all products supplied by a specific supplier that are expired
MATCH (s:Supplier {name: 'PharmaInc'})-[:SUPPLIES]->(p:Product)
WHERE p.expiry_date < date()
RETURN p.name, p.quantity
```

```cypher
// Traceability: Find which supplier provided the product for a specific sale
MATCH (sale:Sale {id: 1042})-[:SOLD_ITEM]->(p:Product)<-[:SUPPLIES]-(s:Supplier)
RETURN s.name, s.phone
```

**Update Operations (Restocking):**
```cypher
MATCH (p:Product {name: 'Cetirizine 10mg'})
SET p.quantity = p.quantity + 50
RETURN p
```

---

## 5. Comparative Analysis: Relational DB vs. Graph DB

| Feature | Relational Database (SQL) | Graph Database (Neo4j) |
| :--- | :--- | :--- |
| **Data Structure** | Rigid Tables, Rows, and Columns. | Flexible Nodes and Relationships. |
| **Relationships** | Requires Foreign Keys and expensive `JOIN` operations. | Relationships are first-class citizens; treated as actual data points. |
| **Query Language** | SQL (Structured Query Language). Can become massive and unreadable for deep relationships. | Cypher. Designed specifically for pattern matching and drawing paths. |
| **Supply Chain Traceability** | Slow. Requires recursive queries or multiple nested joins to trace from Supplier to Sale. | Instantaneous. Graph traversal natively follows the physical flow of goods. |
| **Best Use Case in PharmaCore** | User authentication, exact ledger accounting, and flat reports. | Network topology, supply chain tracking, and visual analytics. |

**Conclusion:**
By combining the strengths of a Relational Database for flat transactional data with a Graph Database for relationship analytics, **PharmaCore** achieves a modern, highly scalable, and analytically powerful architecture.
