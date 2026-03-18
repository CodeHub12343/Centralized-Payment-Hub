import { useEffect, useState } from "react";
import { Search, Download, Filter, AlertCircle } from "lucide-react";
import { StatusBadge } from "../components/StatusBadge";
import { transactionAPI } from "../api/client";

interface Transaction {
  tx_id: string;
  site: string;
  order_id: string;
  amount: number;
  currency: string;
  status: "success" | "pending" | "failed";
  created_at: string;
}

export function Transactions() {
  const [transactions, setTransactions] = useState<Transaction[]>([]);
  const [filteredTransactions, setFilteredTransactions] = useState<Transaction[]>([]);
  const [searchTerm, setSearchTerm] = useState("");
  const [filterSite, setFilterSite] = useState("all");
  const [filterStatus, setFilterStatus] = useState("all");
  const [isLoading, setIsLoading] = useState(true);
  const [isLoadingMore, setIsLoadingMore] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [sites, setSites] = useState<string[]>(["all"]);
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(0);
  const [totalCount, setTotalCount] = useState(0);
  const PER_PAGE = 50;

  // Fetch transactions on mount and when page changes
  useEffect(() => {
    const fetchTransactions = async () => {
      const isInitialLoad = currentPage === 1;
      if (isInitialLoad) {
        setIsLoading(true);
      } else {
        setIsLoadingMore(true);
      }
      setError(null);
      try {
        const response = await transactionAPI.getTransactions({
          page: currentPage,
          per_page: PER_PAGE,
        });
        if (response.success) {
          if (isInitialLoad) {
            setTransactions(response.data);
          } else {
            setTransactions((prev) => [...prev, ...response.data]);
          }
          // Extract unique sites
          const uniqueSites = ["all", ...new Set(response.data.map((t: Transaction) => t.site))];
          setSites(uniqueSites as string[]);
          setTotalCount(response.pagination?.total || 0);
          setTotalPages(response.pagination?.pages || 0);
        }
      } catch (err: any) {
        setError(err.response?.data?.error || "Failed to load transactions");
        console.error("Transactions error:", err);
      } finally {
        if (isInitialLoad) {
          setIsLoading(false);
        } else {
          setIsLoadingMore(false);
        }
      }
    };

    fetchTransactions();
  }, [currentPage]);

  // Apply filters
  useEffect(() => {
    const filtered = transactions.filter((transaction) => {
      const matchesSearch =
        transaction.tx_id.toLowerCase().includes(searchTerm.toLowerCase()) ||
        transaction.order_id.toLowerCase().includes(searchTerm.toLowerCase());
      const matchesSite = filterSite === "all" || transaction.site === filterSite;
      const matchesStatus =
        filterStatus === "all" || transaction.status === filterStatus;
      return matchesSearch && matchesSite && matchesStatus;
    });
    setFilteredTransactions(filtered);
  }, [searchTerm, filterSite, filterStatus, transactions]);

  const handleExportCSV = () => {
    const headers = [
      "Transaction ID",
      "Site",
      "Order ID",
      "Amount",
      "Currency",
      "Status",
      "Created At",
    ];
    const csvData = [
      headers.join(","),
      ...filteredTransactions.map((t) =>
        [
          t.tx_id,
          t.site,
          t.order_id,
          t.amount,
          t.currency,
          t.status,
          t.created_at,
        ]
          .map((field) => `"${field}"`)
          .join(",")
      ),
    ].join("\n");

    const blob = new Blob([csvData], { type: "text/csv" });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `transactions-${new Date().toISOString().split("T")[0]}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
  };

  if (isLoading) {
    return (
      <div className="p-6 flex items-center justify-center min-h-screen">
        <div className="text-center">
          <div className="w-12 h-12 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mx-auto mb-4" />
          <p className="text-gray-600">Loading transactions...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="p-6 space-y-6">
      {/* Page Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold text-gray-900">Transactions</h1>
          <p className="text-gray-600 mt-1">
            View and manage all payment transactions
          </p>
        </div>
        <button
          onClick={handleExportCSV}
          className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
          <Download className="w-4 h-4" />
          Export CSV
        </button>
      </div>

      {/* Error Banner */}
      {error && (
        <div className="bg-red-50 border border-red-200 rounded-lg p-4 flex items-start gap-3">
          <AlertCircle className="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" />
          <div>
            <h3 className="font-medium text-red-900">Error loading transactions</h3>
            <p className="text-sm text-red-700 mt-1">{error}</p>
          </div>
        </div>
      )}

      {/* Filters */}
      <div className="bg-white rounded-lg border border-gray-200 p-6">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
            <input
              type="text"
              placeholder="Search by TX ID or Order ID..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          <div className="relative">
            <Filter className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
            <select
              value={filterSite}
              onChange={(e) => setFilterSite(e.target.value)}
              className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none bg-white"
            >
              {sites.map((site) => (
                <option key={site} value={site}>
                  {site === "all" ? "All Sites" : site}
                </option>
              ))}
            </select>
          </div>

          <div className="relative">
            <Filter className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
            <select
              value={filterStatus}
              onChange={(e) => setFilterStatus(e.target.value)}
              className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none bg-white"
            >
              <option value="all">All Status</option>
              <option value="success">Success</option>
              <option value="pending">Pending</option>
              <option value="failed">Failed</option>
            </select>
          </div>
        </div>

        <div className="mt-4 flex items-center justify-between text-sm">
          <p className="text-gray-600">
            Showing {filteredTransactions.length} of {transactions.length} transactions
          </p>
          {(searchTerm || filterSite !== "all" || filterStatus !== "all") && (
            <button
              onClick={() => {
                setSearchTerm("");
                setFilterSite("all");
                setFilterStatus("all");
              }}
              className="text-blue-600 hover:text-blue-700 font-medium"
            >
              Clear filters
            </button>
          )}
        </div>
      </div>

      {/* Transactions Table */}
      <div className="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50 border-b border-gray-200">
              <tr>
                <th className="text-left text-xs font-medium text-gray-500 uppercase tracking-wider px-6 py-3">
                  Transaction
                </th>
                <th className="text-left text-xs font-medium text-gray-500 uppercase tracking-wider px-6 py-3">
                  Site
                </th>
                <th className="text-left text-xs font-medium text-gray-500 uppercase tracking-wider px-6 py-3">
                  Amount
                </th>
                <th className="text-left text-xs font-medium text-gray-500 uppercase tracking-wider px-6 py-3">
                  Status
                </th>
                <th className="text-left text-xs font-medium text-gray-500 uppercase tracking-wider px-6 py-3">
                  Date
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {filteredTransactions.map((transaction) => (
                <tr key={transaction.tx_id} className="hover:bg-gray-50">
                  <td className="px-6 py-4">
                    <div className="text-sm font-medium text-gray-900">
                      {transaction.tx_id}
                    </div>
                    <div className="text-sm text-gray-500">
                      Order: {transaction.order_id}
                    </div>
                  </td>
                  <td className="px-6 py-4">
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                      {transaction.site}
                    </span>
                  </td>
                  <td className="px-6 py-4">
                    <div className="text-sm font-medium text-gray-900">
                      {transaction.currency} {transaction.amount.toLocaleString()}
                    </div>
                  </td>
                  <td className="px-6 py-4">
                    <StatusBadge status={transaction.status} />
                  </td>
                  <td className="px-6 py-4">
                    <div className="text-sm text-gray-900">
                      {new Date(transaction.created_at).toLocaleDateString()}
                    </div>
                    <div className="text-sm text-gray-500">
                      {new Date(transaction.created_at).toLocaleTimeString()}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        {filteredTransactions.length === 0 && (
          <div className="text-center py-12">
            <p className="text-gray-500">No transactions found</p>
            <p className="text-sm text-gray-400 mt-1">
              Try adjusting your search or filter criteria
            </p>
          </div>
        )}
      </div>

      {/* Pagination Controls */}
      {totalPages > 1 && (
        <div className="bg-white rounded-lg border border-gray-200 p-6 flex items-center justify-between">
          <div className="text-sm text-gray-600">
            Page <span className="font-medium">{currentPage}</span> of{" "}
            <span className="font-medium">{totalPages}</span> ({totalCount} total)
          </div>
          <div className="flex items-center gap-2">
            <button
              onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
              disabled={currentPage === 1 || isLoadingMore}
              className="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Previous
            </button>
            <button
              onClick={() => setCurrentPage((p) => (p < totalPages ? p + 1 : p))}
              disabled={currentPage === totalPages || isLoadingMore}
              className="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              {isLoadingMore ? (
                <>
                  <div className="w-4 h-4 border-2 border-gray-300 border-t-gray-700 rounded-full animate-spin" />
                  Loading...
                </>
              ) : (
                "Next"
              )}
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
