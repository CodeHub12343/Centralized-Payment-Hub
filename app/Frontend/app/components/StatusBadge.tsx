interface StatusBadgeProps {
  status: "success" | "pending" | "failed" | "active" | "down" | "maintenance";
}

export function StatusBadge({ status }: StatusBadgeProps) {
  const styles = {
    success: "bg-green-100 text-green-700 border-green-200",
    pending: "bg-yellow-100 text-yellow-700 border-yellow-200",
    failed: "bg-red-100 text-red-700 border-red-200",
    active: "bg-green-100 text-green-700 border-green-200",
    down: "bg-red-100 text-red-700 border-red-200",
    maintenance: "bg-orange-100 text-orange-700 border-orange-200",
  };

  const labels = {
    success: "Success",
    pending: "Pending",
    failed: "Failed",
    active: "Active",
    down: "Down",
    maintenance: "Maintenance",
  };

  return (
    <span
      className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border ${styles[status]}`}
    >
      {labels[status]}
    </span>
  );
}
