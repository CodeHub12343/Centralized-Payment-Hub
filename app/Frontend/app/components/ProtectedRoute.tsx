import { Navigate } from 'react-router';
import { useAuthStore } from '../stores/authStore';
import { useEffect } from 'react';

interface ProtectedRouteProps {
  element: React.ReactNode;
}

export function ProtectedRoute({ element }: ProtectedRouteProps) {
  const { isAuthenticated, initializeAuth, isLoading } = useAuthStore();

  useEffect(() => {
    // Initialize auth from localStorage on mount
    if (!isAuthenticated && !isLoading) {
      initializeAuth();
    }
  }, []);

  if (isLoading || (!isAuthenticated && localStorage.getItem('auth_token'))) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-center">
          <div className="w-12 h-12 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mx-auto mb-4" />
          <p className="text-gray-600">Loading...</p>
        </div>
      </div>
    );
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  return element;
}
