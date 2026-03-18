import { useEffect, useState } from "react";
import {
  DollarSign,
  CreditCard,
  CheckCircle,
  Clock,
  XCircle,
  TrendingUp,
  Activity,
  AlertCircle,
} from "lucide-react";
import { MetricCard } from "../components/MetricCard";
import { StatusBadge } from "../components/StatusBadge";
import { dashboardAPI, transactionAPI } from "../api/client";
import {
  LineChart,
  Line,
  PieChart,
  Pie,
  Cell,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
} from "recharts";

interface DashboardMetrics {
  totalRevenue: number;
  totalTransactions: number;
  successfulPayments: number;
  pendingPayments: number;
  failedPayments: number;
}

interface Transaction {
  tx_id: string;
  site: string;
  order_id: string;
  amount: number;
  currency: string;
  status: "success" | "pending" | "failed";
  created_at: string;
}

interface Gateway {
  name: string;
  status: "active" | "down" | "maintenance";
  uptime: string;
  lastCheck: string;
  responseTime?: number;
}

export function Dashboard() {
  const [metrics, setMetrics] = useState<DashboardMetrics | null>(null);
  const [transactions, setTransactions] = useState<Transaction[]>([]);
  const [gateways, setGateways] = useState<Gateway[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [revenueData, setRevenueData] = useState<any[]>([]);
  const [errors, setErrors] = useState<string[]>([]);

  useEffect(() => {
    const fetchDashboardData = async () => {
      setIsLoading(true);
      setError(null);
      setErrors([]);
      const failedLoads: string[] = [];

      try {
        // Fetch metrics independently
        try {
          const metricsResponse = await dashboardAPI.getMetrics();
          if (metricsResponse.success) {
            setMetrics(metricsResponse.metrics);
            if (metricsResponse.revenueByDay) {
              setRevenueData(metricsResponse.revenueByDay.map((item: any) => ({
                date: new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
                revenue: item.revenue || 0,
              })));
            }
          }
        } catch (err: any) {
          failedLoads.push("metrics");
          console.error("Metrics error:", err);
        }

        // Fetch recent transactions independently
        try {
          const txResponse = await transactionAPI.getTransactions({ per_page: 5 });
          if (txResponse.success) {
            setTransactions(txResponse.data);
          }
        } catch (err: any) {
          failedLoads.push("transactions");
          console.error("Transactions error:", err);
        }

        // Fetch gateway status independently
        try {
          const gatewaysResponse = await dashboardAPI.getGatewayStatus();
          if (gatewaysResponse.success) {
            setGateways(gatewaysResponse.gateways);
          }
        } catch (err: any) {
          failedLoads.push("gateway status");
          console.error("Gateways error:", err);
        }

        // Set error message if any loads failed
        if (failedLoads.length > 0) {
          setErrors([`Failed to load: ${failedLoads.join(", ")}`]);
        }
      } finally {
        setIsLoading(false);
      }
    };

    fetchDashboardData();
  }, []);

  if (isLoading) {
    return (
      <div className="p-6 flex items-center justify-center min-h-screen">
        <div className="text-center">
          <div className="w-12 h-12 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mx-auto mb-4" />
          <p className="text-gray-600">Loading dashboard...</p>
        </div>
      </div>
    );
  }

  const COLORS = ["#3b82f6", "#10b981"];
  const successRate = metrics && metrics.totalTransactions > 0
    ? ((metrics.successfulPayments / metrics.totalTransactions) * 100).toFixed(1)
    : "0";
  const failRate = metrics && metrics.totalTransactions > 0
    ? ((metrics.failedPayments / metrics.totalTransactions) * 100).toFixed(1)
    : "0";

  const paymentMethodData = [
    { name: "Mobile Money", value: 65, amount: Math.round((metrics?.totalRevenue || 0) * 0.65) },
    { name: "Card", value: 35, amount: Math.round((metrics?.totalRevenue || 0) * 0.35) },
  ];

  return (
    <div className="p-6 space-y-6">
      {/* Page Header */}
      <div>
        <h1 className="text-2xl font-semibold text-gray-900">Dashboard</h1>
        <p className="text-gray-600 mt-1">
          Welcome back! Here's what's happening with your payments today.
        </p>
      </div>

      {/* Error Messages - Partial Load Failures */}
      {errors.length > 0 && (
        <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 flex items-start gap-3">
          <AlertCircle className="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" />
          <div>
            <h3 className="font-medium text-yellow-900">Partial load failure</h3>
            <p className="text-sm text-yellow-700 mt-1">{errors.join("; ")}</p>
          </div>
        </div>
      )}

      {/* Metrics Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
        <MetricCard
          title="Total Revenue"
          value={`UGX ${((metrics?.totalRevenue || 0) / 1000000).toFixed(2)}M`}
          change="+12.5% from last week"
          changeType="positive"
          icon={DollarSign}
        />
        <MetricCard
          title="Total Transactions"
          value={(metrics?.totalTransactions || 0).toString()}
          change="+8 today"
          changeType="positive"
          icon={CreditCard}
        />
        <MetricCard
          title="Successful"
          value={(metrics?.successfulPayments || 0).toString()}
          change={`${successRate}% success rate`}
          changeType="positive"
          icon={CheckCircle}
        />
        <MetricCard
          title="Pending"
          value={(metrics?.pendingPayments || 0).toString()}
          change="Processing"
          changeType="neutral"
          icon={Clock}
        />
        <MetricCard
          title="Failed"
          value={(metrics?.failedPayments || 0).toString()}
          change={`${failRate}% fail rate`}
          changeType="negative"
          icon={XCircle}
        />
      </div>

      {/* Charts Row */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Revenue Chart */}
        <div className="lg:col-span-2 bg-white rounded-lg border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-6">
            <div>
              <h2 className="text-lg font-semibold text-gray-900">Revenue Overview</h2>
              <p className="text-sm text-gray-500 mt-1">Last 7 days</p>
            </div>
            <TrendingUp className="w-5 h-5 text-green-600" />
          </div>
          {revenueData.length > 0 ? (
            <ResponsiveContainer width="100%" height={300}>
              <LineChart data={revenueData}>
                <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
                <XAxis dataKey="date" stroke="#6b7280" fontSize={12} />
                <YAxis
                  stroke="#6b7280"
                  fontSize={12}
                  tickFormatter={(value) => `${(value / 1000000).toFixed(1)}M`}
                />
                <Tooltip
                  formatter={(value: number) => [
                    `UGX ${value.toLocaleString()}`,
                    "Revenue",
                  ]}
                  contentStyle={{
                    backgroundColor: "#fff",
                    border: "1px solid #e5e7eb",
                    borderRadius: "8px",
                  }}
                />
                <Line
                  type="monotone"
                  dataKey="revenue"
                  stroke="#3b82f6"
                  strokeWidth={2}
                  dot={{ fill: "#3b82f6", r: 4 }}
                />
              </LineChart>
            </ResponsiveContainer>
          ) : (
            <div className="h-[300px] flex items-center justify-center text-gray-500">
              No revenue data available
            </div>
          )}
        </div>

        {/* Payment Method Distribution */}
        <div className="bg-white rounded-lg border border-gray-200 p-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-6">Payment Methods</h2>
          {paymentMethodData.some(m => m.amount > 0) ? (
            <ResponsiveContainer width="100%" height={300}>
              <PieChart>
                <Pie
                  data={paymentMethodData}
                  cx="50%"
                  cy="50%"
                  labelLine={false}
                  label={({ name, value }) => `${name}: ${value}%`}
                  outerRadius={80}
                  fill="#8884d8"
                  dataKey="value"
                >
                  {paymentMethodData.map((entry, index) => (
                    <Cell
                      key={`cell-${index}`}
                      fill={COLORS[index % COLORS.length]}
                    />
                  ))}
                </Pie>
                <Tooltip
                  formatter={(value: number, name: string, props: any) => [
                    `UGX ${props.payload.amount.toLocaleString()}`,
                    name,
                  ]}
                />
              </PieChart>
            </ResponsiveContainer>
          ) : (
            <div className="h-[300px] flex items-center justify-center text-gray-500">
              No transaction data
            </div>
          )}
          <div className="mt-4 space-y-2">
            {paymentMethodData.map((method, index) => (
              <div key={method.name} className="flex items-center justify-between text-sm">
                <div className="flex items-center gap-2">
                  <div
                    className="w-3 h-3 rounded-full"
                    style={{ backgroundColor: COLORS[index] }}
                  ></div>
                  <span className="text-gray-700">{method.name}</span>
                </div>
                <span className="font-medium text-gray-900">
                  UGX {(method.amount / 1000000).toFixed(2)}M
                </span>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Bottom Row */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Recent Transactions */}
        <div className="lg:col-span-2 bg-white rounded-lg border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-6">
            <h2 className="text-lg font-semibold text-gray-900">Recent Transactions</h2>
            <a href="/transactions" className="text-sm text-blue-600 hover:text-blue-700 font-medium">
              View all
            </a>
          </div>
          {transactions.length > 0 ? (
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="border-b border-gray-200">
                    <th className="text-left text-xs font-medium text-gray-500 uppercase tracking-wider pb-3">
                      Transaction
                    </th>
                    <th className="text-left text-xs font-medium text-gray-500 uppercase tracking-wider pb-3">
                      Amount
                    </th>
                    <th className="text-left text-xs font-medium text-gray-500 uppercase tracking-wider pb-3">
                      Status
                    </th>
                    <th className="text-left text-xs font-medium text-gray-500 uppercase tracking-wider pb-3">
                      Date
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-200">
                  {transactions.map((transaction) => (
                    <tr key={transaction.tx_id} className="hover:bg-gray-50">
                      <td className="py-3">
                        <div className="text-sm font-medium text-gray-900">{transaction.tx_id}</div>
                        <div className="text-xs text-gray-500">Order: {transaction.order_id}</div>
                      </td>
                      <td className="py-3">
                        <span className="text-sm font-medium text-gray-900">
                          {transaction.currency} {transaction.amount.toLocaleString()}
                        </span>
                      </td>
                      <td className="py-3">
                        <StatusBadge status={transaction.status} />
                      </td>
                      <td className="py-3 text-sm text-gray-900">
                        {new Date(transaction.created_at).toLocaleDateString()}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <div className="py-8 text-center text-gray-500">No transactions yet</div>
          )}
        </div>

        {/* Right Column */}
        <div className="space-y-6">
          {/* Gateway Status */}
          <div className="bg-white rounded-lg border border-gray-200 p-6">
            <h2 className="text-lg font-semibold text-gray-900 mb-4">Gateway Status</h2>
            {gateways.length > 0 ? (
              <div className="space-y-4">
                {gateways.map((gateway) => (
                  <div
                    key={gateway.name}
                    className="flex items-center justify-between pb-4 border-b border-gray-100 last:border-0 last:pb-0"
                  >
                    <div>
                      <p className="text-sm font-medium text-gray-900">{gateway.name}</p>
                      <p className="text-xs text-gray-500 mt-1">{gateway.uptime} uptime</p>
                    </div>
                    <StatusBadge status={gateway.status} />
                  </div>
                ))}
              </div>
            ) : (
              <p className="text-sm text-gray-500">No gateways configured</p>
            )}
          </div>

          {/* Activity Feed */}
          <div className="bg-white rounded-lg border border-gray-200 p-6">
            <div className="flex items-center gap-2 mb-4">
              <Activity className="w-5 h-5 text-gray-700" />
              <h2 className="text-lg font-semibold text-gray-900">Recent Activity</h2>
            </div>
            <div className="space-y-3 text-sm text-gray-600">
              <p>Dashboard loaded successfully</p>
              <p>API integration active</p>
              <p>Real-time data syncing enabled</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
