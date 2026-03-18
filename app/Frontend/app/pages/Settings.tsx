import { useEffect, useState } from "react";
import { Plus, Trash2, Eye, EyeOff, AlertCircle, CheckCircle, Edit } from "lucide-react";
import { websiteAPI, Website } from "../api/client";

export function Settings() {
  const [websites, setWebsites] = useState<Website[]>([]);
  const [showForm, setShowForm] = useState(false);
  const [showEditForm, setShowEditForm] = useState(false);
  const [editingWebsite, setEditingWebsite] = useState<Website | null>(null);
  const [visibleSecrets, setVisibleSecrets] = useState<Set<string>>(new Set());
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [formData, setFormData] = useState({
    site_code: "",
    secret_key: "",
    success_url: "",
    fail_url: "",
  });
  const [editFormData, setEditFormData] = useState({
    secret_key: "",
    success_url: "",
    fail_url: "",
    is_active: true,
  });

  // Fetch websites on mount
  useEffect(() => {
    const fetchWebsites = async () => {
      setIsLoading(true);
      setError(null);
      try {
        const response = await websiteAPI.getWebsites();
        if (response.success) {
          setWebsites(response.data);
        }
      } catch (err: any) {
        setError(err.response?.data?.error || "Failed to load websites");
        console.error("Settings error:", err);
      } finally {
        setIsLoading(false);
      }
    };

    fetchWebsites();
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    setError(null);
    setSuccess(null);

    try {
      const response = await websiteAPI.createWebsite(formData);
      if (response.success) {
        setSuccess(`Website ${formData.site_code} created successfully`);
        setFormData({
          site_code: "",
          secret_key: "",
          success_url: "",
          fail_url: "",
        });
        setShowForm(false);
        
        // Refresh websites list
        const refreshResponse = await websiteAPI.getWebsites();
        if (refreshResponse.success) {
          setWebsites(refreshResponse.data);
        }
        
        // Clear success message after 3 seconds
        setTimeout(() => setSuccess(null), 3000);
      } else {
        setError(response.error || "Failed to create website");
      }
    } catch (err: any) {
      const errorMsg = err.response?.data?.error || err.message || "Failed to create website";
      setError(errorMsg);
      console.error("Submit error:", err);
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleFormInputChange = (field: keyof typeof formData, value: string) => {
    setFormData({ ...formData, [field]: value });
    // Clear error when user starts typing
    if (error) {
      setError(null);
    }
  };

  const handleEditFormInputChange = (field: keyof typeof editFormData, value: any) => {
    setEditFormData({ ...editFormData, [field]: value });
    // Clear error when user starts typing
    if (error) {
      setError(null);
    }
  };

  const handleDelete = async (siteCode: string) => {
    if (!window.confirm(`Are you sure you want to remove ${siteCode}?`)) {
      return;
    }

    setError(null);
    setSuccess(null);

    try {
      const response = await websiteAPI.deleteWebsite(siteCode);
      if (response.success) {
        setSuccess(`Website ${siteCode} deleted successfully`);
        setWebsites(websites.filter((w) => w.site_code !== siteCode));
        setTimeout(() => setSuccess(null), 3000);
      } else {
        setError(response.error || "Failed to delete website");
      }
    } catch (err: any) {
      setError(err.response?.data?.error || "Failed to delete website");
      console.error("Delete error:", err);
    }
  };

  const handleStartEdit = (website: Website) => {
    setEditingWebsite(website);
    setEditFormData({
      secret_key: website.secret_key,
      success_url: website.success_url,
      fail_url: website.fail_url,
      is_active: website.is_active ? true : false,
    });
    setShowEditForm(true);
    setError(null);
    setSuccess(null);
  };

  const handleEditSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingWebsite) return;

    setIsSubmitting(true);
    setError(null);
    setSuccess(null);

    try {
      const response = await websiteAPI.updateWebsite(
        editingWebsite.site_code,
        editFormData
      );
      if (response.success) {
        setSuccess(
          `Website ${editingWebsite.site_code} updated successfully`
        );

        // Refresh websites list
        const refreshResponse = await websiteAPI.getWebsites();
        if (refreshResponse.success) {
          setWebsites(refreshResponse.data);
        }

        setShowEditForm(false);
        setEditingWebsite(null);
        setTimeout(() => setSuccess(null), 3000);
      } else {
        setError(response.error || "Failed to update website");
      }
    } catch (err: any) {
      const errorMsg = err.response?.data?.error || err.message || "Failed to update website";
      setError(errorMsg);
      console.error("Update error:", err);
    } finally {
      setIsSubmitting(false);
    }
  };

  const toggleSecretVisibility = (siteCode: string) => {
    const newVisible = new Set(visibleSecrets);
    if (newVisible.has(siteCode)) {
      newVisible.delete(siteCode);
    } else {
      newVisible.add(siteCode);
    }
    setVisibleSecrets(newVisible);
  };

  const generateSecretKey = () => {
    const chars =
      "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    const key =
      "sk_live_" +
      Array.from({ length: 24 }, () =>
        chars.charAt(Math.floor(Math.random() * chars.length))
      ).join("");
    setFormData({ ...formData, secret_key: key });
  };

  if (isLoading) {
    return (
      <div className="p-6 flex items-center justify-center min-h-screen">
        <div className="text-center">
          <div className="w-12 h-12 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mx-auto mb-4" />
          <p className="text-gray-600">Loading settings...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="p-6 space-y-6">
      {/* Page Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold text-gray-900">Settings</h1>
          <p className="text-gray-600 mt-1">
            Manage websites and payment configurations
          </p>
        </div>
        <button
          onClick={() => setShowForm(!showForm)}
          className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
          <Plus className="w-4 h-4" />
          Add Website
        </button>
      </div>

      {/* Success Message */}
      {success && (
        <div className="bg-green-50 border border-green-200 rounded-lg p-4 flex items-start gap-3">
          <CheckCircle className="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" />
          <div>
            <p className="text-sm text-green-700">{success}</p>
          </div>
        </div>
      )}

      {/* Error Message */}
      {error && (
        <div className="bg-red-50 border border-red-200 rounded-lg p-4 flex items-start gap-3">
          <AlertCircle className="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" />
          <div>
            <p className="text-sm text-red-700">{error}</p>
          </div>
        </div>
      )}

      {/* Add Website Form */}
      {showForm && (
        <div className="bg-white rounded-lg border border-gray-200 p-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">
            Add New Website
          </h2>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Site Code *
                </label>
                <input
                  type="text"
                  required
                  placeholder="e.g., shop-d"
                  value={formData.site_code}
                  onChange={(e) =>
                    handleFormInputChange("site_code", e.target.value)
                  }
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  disabled={isSubmitting}
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Secret Key *
                </label>
                <div className="flex gap-2">
                  <input
                    type="text"
                    required
                    placeholder="sk_live_..."
                    value={formData.secret_key}
                    onChange={(e) =>
                      handleFormInputChange("secret_key", e.target.value)
                    }
                    className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    disabled={isSubmitting}
                  />
                  <button
                    type="button"
                    onClick={generateSecretKey}
                    disabled={isSubmitting}
                    className="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Generate
                  </button>
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Success URL *
                </label>
                <input
                  type="url"
                  required
                  placeholder="https://yoursite.com/payment/success"
                  value={formData.success_url}
                  onChange={(e) =>
                    handleFormInputChange("success_url", e.target.value)
                  }
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  disabled={isSubmitting}
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Fail URL *
                </label>
                <input
                  type="url"
                  required
                  placeholder="https://yoursite.com/payment/fail"
                  value={formData.fail_url}
                  onChange={(e) =>
                    handleFormInputChange("fail_url", e.target.value)
                  }
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  disabled={isSubmitting}
                />
              </div>
            </div>

            <div className="flex gap-3 pt-4">
              <button
                type="submit"
                disabled={isSubmitting}
                className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:bg-blue-400 disabled:cursor-not-allowed flex items-center gap-2"
              >
                {isSubmitting ? (
                  <>
                    <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                    Creating...
                  </>
                ) : (
                  "Add Website"
                )}
              </button>
              <button
                type="button"
                onClick={() => setShowForm(false)}
                disabled={isSubmitting}
                className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Cancel
              </button>
            </div>
          </form>
        </div>
      )}

      {/* Websites List */}
      <div className="bg-white rounded-lg border border-gray-200">
        <div className="px-6 py-4 border-b border-gray-200">
          <h2 className="text-lg font-semibold text-gray-900">
            Configured Websites ({websites.length})
          </h2>
        </div>

        {websites.length > 0 ? (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50 border-b border-gray-200">
                <tr>
                  <th className="text-left text-xs font-medium text-gray-500 uppercase tracking-wider px-6 py-3">
                    Site Code
                  </th>
                  <th className="text-left text-xs font-medium text-gray-500 uppercase tracking-wider px-6 py-3">
                    Secret Key
                  </th>
                  <th className="text-left text-xs font-medium text-gray-500 uppercase tracking-wider px-6 py-3">
                    Created
                  </th>
                  <th className="text-right text-xs font-medium text-gray-500 uppercase tracking-wider px-6 py-3">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {websites.map((website) => (
                  <tr key={website.site_code} className="hover:bg-gray-50">
                    <td className="px-6 py-4">
                      <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        {website.site_code}
                      </span>
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex items-center gap-2">
                        <code className="text-sm text-gray-900 font-mono bg-gray-100 px-2 py-1 rounded">
                          {visibleSecrets.has(website.site_code)
                            ? website.secret_key
                            : "•".repeat(20)}
                        </code>
                        <button
                          onClick={() => toggleSecretVisibility(website.site_code)}
                          className="p-1 text-gray-400 hover:text-gray-600 rounded"
                        >
                          {visibleSecrets.has(website.site_code) ? (
                            <EyeOff className="w-4 h-4" />
                          ) : (
                            <Eye className="w-4 h-4" />
                          )}
                        </button>
                      </div>
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-900">
                      {website.created_at
                        ? new Date(website.created_at).toLocaleDateString()
                        : "N/A"}
                    </td>
                    <td className="px-6 py-4 text-right">
                      <div className="flex items-center justify-end gap-2">
                        <button
                          onClick={() => handleStartEdit(website)}
                          className="inline-flex items-center gap-2 px-3 py-2 text-sm text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded transition-colors"
                        >
                          <Edit className="w-4 h-4" />
                          Edit
                        </button>
                        <button
                          onClick={() => handleDelete(website.site_code!)}
                          className="inline-flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:text-red-700 hover:bg-red-50 rounded transition-colors"
                        >
                          <Trash2 className="w-4 h-4" />
                          Delete
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <div className="px-6 py-12 text-center">
            <p className="text-gray-500 font-medium">No websites configured yet</p>
            <p className="text-sm text-gray-400 mt-1">Click "Add Website" to create your first website configuration</p>
          </div>
        )}
      </div>

      {/* Edit Website Form Modal */}
      {showEditForm && editingWebsite && (
        <div className="bg-white rounded-lg border border-gray-200 p-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">
            Edit Website: {editingWebsite.site_code}
          </h2>
          <form onSubmit={handleEditSubmit} className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Secret Key *
                </label>
                <div className="flex gap-2">
                  <input
                    type="text"
                    required
                    placeholder="sk_live_..."
                    value={editFormData.secret_key}
                    onChange={(e) =>
                      handleEditFormInputChange("secret_key", e.target.value)
                    }
                    className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    disabled={isSubmitting}
                  />
                  <button
                    type="button"
                    onClick={() => {
                      const chars =
                        "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
                      const key =
                        "sk_live_" +
                        Array.from({ length: 24 }, () =>
                          chars.charAt(Math.floor(Math.random() * chars.length))
                        ).join("");
                      setEditFormData({
                        ...editFormData,
                        secret_key: key,
                      });
                    }}
                    disabled={isSubmitting}
                    className="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Generate
                  </button>
                </div>
              </div>

              <div></div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Success URL *
                </label>
                <input
                  type="url"
                  required
                  placeholder="https://yoursite.com/payment/success"
                  value={editFormData.success_url}
                  onChange={(e) =>
                    handleEditFormInputChange("success_url", e.target.value)
                  }
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  disabled={isSubmitting}
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Fail URL *
                </label>
                <input
                  type="url"
                  required
                  placeholder="https://yoursite.com/payment/fail"
                  value={editFormData.fail_url}
                  onChange={(e) =>
                    handleEditFormInputChange("fail_url", e.target.value)
                  }
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  disabled={isSubmitting}
                />
              </div>
            </div>

            <div className="flex items-center gap-4">
              <label className="flex items-center gap-2">
                <input
                  type="checkbox"
                  checked={editFormData.is_active}
                  onChange={(e) =>
                    setEditFormData({
                      ...editFormData,
                      is_active: e.target.checked,
                    })
                  }
                  disabled={isSubmitting}
                  className="rounded border-gray-300"
                />
                <span className="text-sm text-gray-700">Active</span>
              </label>
            </div>

            <div className="flex gap-3 pt-4">
              <button
                type="submit"
                disabled={isSubmitting}
                className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:bg-blue-400 disabled:cursor-not-allowed flex items-center gap-2"
              >
                {isSubmitting ? (
                  <>
                    <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                    Updating...
                  </>
                ) : (
                  "Update Website"
                )}
              </button>
              <button
                type="button"
                onClick={() => {
                  setShowEditForm(false);
                  setEditingWebsite(null);
                }}
                disabled={isSubmitting}
                className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Cancel
              </button>
            </div>
          </form>
        </div>
      )}

      {/* Token System Info */}
      <div className="bg-white rounded-lg border border-gray-200 p-6">
        <h2 className="text-lg font-semibold text-gray-900 mb-4">
          Token System Configuration
        </h2>
        <div className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="p-4 bg-gray-50 rounded-lg">
              <p className="text-sm font-medium text-gray-700">
                Token Expiration
              </p>
              <p className="text-2xl font-semibold text-gray-900 mt-1">
                30 minutes
              </p>
              <p className="text-xs text-gray-500 mt-1">
                Tokens expire after this duration for security
              </p>
            </div>

            <div className="p-4 bg-gray-50 rounded-lg">
              <p className="text-sm font-medium text-gray-700">
                Signature Algorithm
              </p>
              <p className="text-2xl font-semibold text-gray-900 mt-1">
                SHA256
              </p>
              <p className="text-xs text-gray-500 mt-1">
                Used to validate token authenticity
              </p>
            </div>
          </div>

          <div className="p-4 bg-blue-50 rounded-lg border border-blue-100">
            <p className="text-sm text-blue-900 font-medium">
              Payment Hub URL Format
            </p>
            <code className="block text-sm text-blue-700 mt-2 font-mono">
              https://pay.yourdomain.com/pay/&#123;token&#125;
            </code>
            <p className="text-xs text-blue-700 mt-2">
              Use this format when redirecting users from your CMS to the
              payment hub
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
