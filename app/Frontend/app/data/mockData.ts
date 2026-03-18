export interface Transaction {
  tx_id: string;
  site: string;
  order_id: string;
  customer: string;
  amount: number;
  currency: string;
  method: string;
  status: "success" | "pending" | "failed";
  provider_ref: string;
  created_at: string;
}

export interface Website {
  site_code: string;
  secret_key: string;
  success_url: string;
  fail_url: string;
  created_at: string;
}

export interface Gateway {
  name: string;
  status: "active" | "down" | "maintenance";
  uptime: string;
  lastCheck: string;
}

export interface Activity {
  id: string;
  type: "payment" | "refund" | "link" | "webhook" | "customer";
  message: string;
  timestamp: string;
}

export const mockTransactions: Transaction[] = [
  {
    tx_id: "TX-2026-001523",
    site: "shop-a",
    order_id: "ORD-4781",
    customer: "John Doe",
    amount: 45000,
    currency: "UGX",
    method: "Mobile Money",
    status: "success",
    provider_ref: "PP-789456123",
    created_at: "2026-03-15 14:32:15",
  },
  {
    tx_id: "TX-2026-001522",
    site: "shop-b",
    order_id: "ORD-9821",
    customer: "Jane Smith",
    amount: 120000,
    currency: "UGX",
    method: "Card",
    status: "success",
    provider_ref: "PP-789456122",
    created_at: "2026-03-15 14:28:42",
  },
  {
    tx_id: "TX-2026-001521",
    site: "shop-a",
    order_id: "ORD-7412",
    customer: "Mike Johnson",
    amount: 75000,
    currency: "UGX",
    method: "Mobile Money",
    status: "pending",
    provider_ref: "PP-789456121",
    created_at: "2026-03-15 14:15:08",
  },
  {
    tx_id: "TX-2026-001520",
    site: "shop-c",
    order_id: "ORD-3698",
    customer: "Sarah Williams",
    amount: 32000,
    currency: "UGX",
    method: "Mobile Money",
    status: "failed",
    provider_ref: "PP-789456120",
    created_at: "2026-03-15 13:58:33",
  },
  {
    tx_id: "TX-2026-001519",
    site: "shop-b",
    order_id: "ORD-5547",
    customer: "David Brown",
    amount: 95000,
    currency: "UGX",
    method: "Card",
    status: "success",
    provider_ref: "PP-789456119",
    created_at: "2026-03-15 13:45:21",
  },
  {
    tx_id: "TX-2026-001518",
    site: "shop-a",
    order_id: "ORD-8823",
    customer: "Emily Davis",
    amount: 58000,
    currency: "UGX",
    method: "Mobile Money",
    status: "success",
    provider_ref: "PP-789456118",
    created_at: "2026-03-15 13:22:54",
  },
  {
    tx_id: "TX-2026-001517",
    site: "shop-c",
    order_id: "ORD-1147",
    customer: "Chris Wilson",
    amount: 112000,
    currency: "UGX",
    method: "Card",
    status: "success",
    provider_ref: "PP-789456117",
    created_at: "2026-03-15 12:55:17",
  },
  {
    tx_id: "TX-2026-001516",
    site: "shop-a",
    order_id: "ORD-9954",
    customer: "Amanda Taylor",
    amount: 67000,
    currency: "UGX",
    method: "Mobile Money",
    status: "pending",
    provider_ref: "PP-789456116",
    created_at: "2026-03-15 12:38:42",
  },
];

export const mockWebsites: Website[] = [
  {
    site_code: "shop-a",
    secret_key: "sk_live_a1b2c3d4e5f6g7h8",
    success_url: "https://shop-a.com/payment/success",
    fail_url: "https://shop-a.com/payment/fail",
    created_at: "2026-01-10 09:15:00",
  },
  {
    site_code: "shop-b",
    secret_key: "sk_live_z9y8x7w6v5u4t3s2",
    success_url: "https://shop-b.com/checkout/success",
    fail_url: "https://shop-b.com/checkout/fail",
    created_at: "2026-01-22 14:30:00",
  },
  {
    site_code: "shop-c",
    secret_key: "sk_live_p1q2r3s4t5u6v7w8",
    success_url: "https://shop-c.com/payment-complete",
    fail_url: "https://shop-c.com/payment-failed",
    created_at: "2026-02-05 11:45:00",
  },
];

export const mockGateways: Gateway[] = [
  {
    name: "Pawapay",
    status: "active",
    uptime: "99.8%",
    lastCheck: "2 mins ago",
  },
  {
    name: "Stripe",
    status: "active",
    uptime: "99.9%",
    lastCheck: "1 min ago",
  },
  {
    name: "Flutterwave",
    status: "active",
    uptime: "99.5%",
    lastCheck: "3 mins ago",
  },
];

export const mockActivities: Activity[] = [
  {
    id: "1",
    type: "payment",
    message: "Payment of UGX 45,000 received from John Doe",
    timestamp: "2 mins ago",
  },
  {
    id: "2",
    type: "payment",
    message: "Payment of UGX 120,000 received from Jane Smith",
    timestamp: "6 mins ago",
  },
  {
    id: "3",
    type: "webhook",
    message: "Webhook delivered to shop-a.com",
    timestamp: "12 mins ago",
  },
  {
    id: "4",
    type: "payment",
    message: "Payment of UGX 95,000 received from David Brown",
    timestamp: "29 mins ago",
  },
  {
    id: "5",
    type: "refund",
    message: "Refund of UGX 32,000 issued to Sarah Williams",
    timestamp: "45 mins ago",
  },
];

export const revenueData = [
  { date: "Mar 9", revenue: 850000 },
  { date: "Mar 10", revenue: 920000 },
  { date: "Mar 11", revenue: 1100000 },
  { date: "Mar 12", revenue: 980000 },
  { date: "Mar 13", revenue: 1250000 },
  { date: "Mar 14", revenue: 1180000 },
  { date: "Mar 15", revenue: 1420000 },
];

export const paymentMethodData = [
  { name: "Mobile Money", value: 65, amount: 4550000 },
  { name: "Card", value: 35, amount: 2450000 },
];
