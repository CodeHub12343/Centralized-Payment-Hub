import { useEffect } from "react";
import { RouterProvider } from "react-router";
import { router } from "./routes";
import { useAuthStore } from "./stores/authStore";

export default function App() {
  const { initializeAuth } = useAuthStore();

  useEffect(() => {
    // Initialize authentication from localStorage on app load
    initializeAuth();
  }, []);

  return <RouterProvider router={router} />;
}
