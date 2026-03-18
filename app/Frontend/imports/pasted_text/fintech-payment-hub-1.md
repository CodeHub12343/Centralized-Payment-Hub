{
  "prompt_title": "Fintech Payment Hub Dashboard",

  "role": [
    "Fintech Product Designer",
    "SaaS Dashboard UX Architect",
    "Design System Specialist"
  ],

  "task": "Design a modern SaaS-style dashboard UI for a centralized payment hub used to monitor transactions, revenue, payment gateways, and system health.",

  "design_context": {
    "platform": "Centralized Payment Hub",
    "users": ["Payment Admins", "Finance Teams", "Operations Managers"],
    "inspiration": ["Stripe Dashboard", "Paystack Dashboard", "Flutterwave Dashboard"]
  },

  "design_philosophy": [
    "Data-first dashboard",
    "Clarity over decoration",
    "Fast metric scanning",
    "Modular widget layout",
    "Minimal cognitive load"
  ],

  "layout": {
    "zones": ["Sidebar", "Topbar", "Main Dashboard"],
    "sections": [
      "Metrics Overview",
      "Revenue Analytics",
      "Payment Method Analytics",
      "Recent Transactions Table",
      "Gateway Status",
      "System Alerts",
      "Activity Feed"
    ]
  },

  "sidebar": [
    "Dashboard",
    "Transactions",
    "Payment Links",
    "Customers",
    "Payouts",
    "Analytics",
    "Integrations",
    "Gateways",
    "Webhooks",
    "API Logs",
    "Settings"
  ],

  "topbar": [
    "Search",
    "Date Filter",
    "Notifications",
    "Help",
    "User Profile"
  ],

  "metrics_cards": [
    "Total Revenue",
    "Total Transactions",
    "Successful Payments",
    "Pending Transactions",
    "Failed Payments"
  ],

  "charts": [
    "Revenue Overview (Line Chart)",
    "Payment Method Distribution (Donut Chart)"
  ],

  "transactions_table": {
    "columns": [
      "Transaction ID",
      "Customer",
      "Amount",
      "Method",
      "Status",
      "Date",
      "Actions"
    ]
  },

  "system_widgets": [
    "Payment Gateway Status",
    "System Alerts"
  ],

  "activity_feed": [
    "Payment received",
    "Refund issued",
    "Payment link created",
    "Webhook delivered",
    "Customer created"
  ],

  "ui_patterns": [
    "Card-based widgets",
    "12-column grid layout",
    "Collapsible sidebar",
    "Filterable data tables",
    "Status badges for transactions"
  ],

  "micro_interactions": [
    "Card hover elevation",
    "Table row highlight",
    "Copy-to-clipboard toast",
    "Notification dropdown"
  ],

  "animation_rules": [
    "Subtle motion only",
    "Fast transitions",
    "Avoid heavy animation"
  ],

  "final_instruction": "Generate a clean fintech SaaS dashboard similar to Stripe or Paystack dashboards with modular widgets, analytics charts, transaction tables, and gateway monitoring."
}