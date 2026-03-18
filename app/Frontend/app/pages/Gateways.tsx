import { useEffect, useState } from "react";
import { Activity, CheckCircle, AlertCircle, RefreshCw } from "lucide-react";
import { StatusBadge } from "../components/StatusBadge";
import { dashboardAPI } from "../api/client";

interface Gateway {
  name: string;
  status: "active" | "down" | "maintenance";
  uptime: string;
  lastCheck: string;
  responseTime?: number;
}

export function Gateways() {
  const [gateways, setGateways] = useState<Gateway[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [isRefreshing, setIsRefreshing] = useState(false);

  const fetchGateways = async () => {
    setIsLoading(true);
    setError(null);
    try {
      const response = await dashboardAPI.getGatewayStatus();
      if (response.success) {
        setGateways(response.gateways);
      }
    } catch (err: any) {
      setError(err.response?.data?.error || "Failed to load gateway status");
      console.error("Gateways error:", err);
    } finally {
      setIsLoading(false);
    }
  };

  const handleRefresh = async () => {
    setIsRefreshing(true);
    try {
      const response = await dashboardAPI.getGatewayStatus();
      if (response.success) {
        setGateways(response.gateways);
        setError(null);
      }
    } catch (err: any) {
      setError("Failed to refresh gateway status");
    } finally {
      setIsRefreshing(false);
    }
  };

  useEffect(() => {
    fetchGateways();
  }, []);

  // Calculate metrics from fetched data
  const activeGatewayCount = gateways.filter((g) => g.status === "active").length;
  const averageUptime =
    gateways.length > 0
      ? (
          gateways.reduce((sum, g) => {
            const uptimeNum = parseFloat(g.uptime);
            return sum + (isNaN(uptimeNum) ? 0 : uptimeNum);
          }, 0) / gateways.length
        ).toFixed(1)
      : "0.0";

  if (isLoading) {
    return (
      <div className="p-6 flex items-center justify-center min-h-screen">
        <div className="text-center">
          <div className="w-12 h-12 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mx-auto mb-4" />
          <p className="text-gray-600">Loading gateway status...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="p-6 space-y-6">
      {/* Page Header */}
      <div>
        <h1 className="text-2xl font-semibold text-gray-900">
          Payment Gateways
        </h1>
        <p className="text-gray-600 mt-1">
          Monitor and manage your payment gateway integrations
        </p>
      </div>

      {/* Error Message */}
      {error && (
        <div className="bg-red-50 border border-red-200 rounded-lg p-4 flex items-start gap-3">
          <AlertCircle className="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" />
          <div>
            <p className="text-sm text-red-700">{error}</p>
          </div>
        </div>
      )}

      {/* Gateway Status Overview */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="bg-white rounded-lg border border-gray-200 p-6">
          <div className="flex items-center gap-3">
            <div className="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
              <CheckCircle className="w-6 h-6 text-green-600" />
            </div>
            <div>
              <p className="text-sm text-gray-600">Active Gateways</p>
              <p className="text-2xl font-semibold text-gray-900 mt-1">
                {activeGatewayCount}
              </p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg border border-gray-200 p-6">
          <div className="flex items-center gap-3">
            <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <Activity className="w-6 h-6 text-blue-600" />
            </div>
            <div>
              <p className="text-sm text-gray-600">Average Uptime</p>
              <p className="text-2xl font-semibold text-gray-900 mt-1">{averageUptime}%</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg border border-gray-200 p-6">
          <div className="flex items-center gap-3">
            <div className="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
              <AlertCircle className="w-6 h-6 text-orange-600" />
            </div>
            <div>
              <p className="text-sm text-gray-600">Issues Detected</p>
              <p className="text-2xl font-semibold text-gray-900 mt-1">
                {gateways.filter((g) => g.status === "down").length}
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Gateways List */}
      <div className="bg-white rounded-lg border border-gray-200">
        <div className="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
          <h2 className="text-lg font-semibold text-gray-900">
            Configured Gateways
          </h2>
          <button
            onClick={handleRefresh}
            disabled={isRefreshing}
            className="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:text-gray-900 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <RefreshCw className={`w-4 h-4 ${isRefreshing ? "animate-spin" : ""}`} />
            {isRefreshing ? "Refreshing..." : "Refresh Status"}
          </button>
        </div>

        {gateways.length > 0 ? (
          <div className="divide-y divide-gray-200">
            {gateways.map((gateway) => (
              <div key={gateway.name} className="px-6 py-6 hover:bg-gray-50">
                <div className="flex items-start justify-between">
                  <div className="flex-1">
                    <div className="flex items-center gap-3">
                      <h3 className="text-lg font-medium text-gray-900">
                        {gateway.name}
                      </h3>
                      <StatusBadge status={gateway.status} />
                    </div>

                    <div className="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                      <div>
                        <p className="text-sm text-gray-500">Uptime</p>
                        <p className="text-sm font-medium text-gray-900 mt-1">
                          {gateway.uptime}
                        </p>
                      </div>
                      <div>
                        <p className="text-sm text-gray-500">Last Health Check</p>
                        <p className="text-sm font-medium text-gray-900 mt-1">
                          {new Date(gateway.lastCheck).toLocaleString()}
                        </p>
                      </div>
                      <div>
                        <p className="text-sm text-gray-500">Response Time</p>
                        <p className="text-sm font-medium text-gray-900 mt-1">
                          {gateway.responseTime ? `${gateway.responseTime}ms` : "N/A"}
                        </p>
                      </div>
                    </div>

                    {gateway.name === "Pawapay" && (
                      <div className="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-100">
                        <p className="text-sm text-blue-900 font-medium">
                          Primary Gateway
                        </p>
                        <p className="text-sm text-blue-700 mt-1">
                          This is your main payment processor for mobile money
                          transactions
                        </p>
                      </div>
                    )}
                  </div>

                  <div className="ml-4">
                    <button className="px-4 py-2 text-sm text-gray-700 hover:text-gray-900 border border-gray-300 rounded-lg hover:bg-white transition-colors">
                      Configure
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        ) : (
          <div className="px-6 py-12 text-center">
            <p className="text-gray-500 font-medium">No gateways configured</p>
            <p className="text-sm text-gray-400 mt-1">
              Gateways will appear here once they are configured
            </p>
          </div>
        )}
      </div>

      {/* Webhook Configuration */}
      <div className="bg-white rounded-lg border border-gray-200 p-6">
        <h2 className="text-lg font-semibold text-gray-900 mb-4">
          Webhook Configuration
        </h2>
        <div className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Callback URL
            </label>
            <div className="flex gap-2">
              <input
                type="text"
                value="https://pay.yourdomain.com/webhook"
                readOnly
                className="flex-1 px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-600"
              />
              <button className="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                Copy
              </button>
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Return URL
            </label>
            <div className="flex gap-2">
              <input
                type="text"
                value="https://pay.yourdomain.com/return"
                readOnly
                className="flex-1 px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-600"
              />
              <button className="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                Copy
              </button>
            </div>
          </div>

          <div className="mt-4 p-4 bg-gray-50 rounded-lg">
            <p className="text-sm text-gray-700">
              Configure these URLs in your payment gateway dashboard to receive
              payment notifications and handle return flows.
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
