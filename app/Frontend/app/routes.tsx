import { createBrowserRouter } from "react-router";
import { Dashboard } from "./pages/Dashboard";
import { Transactions } from "./pages/Transactions";
import { Gateways } from "./pages/Gateways";
import { Settings } from "./pages/Settings";
import { Login } from "./pages/Login";
import { Layout } from "./components/Layout";
import { ProtectedRoute } from "./components/ProtectedRoute";

export const router = createBrowserRouter([
  {
    path: "/login",
    Component: Login,
  },
  {
    path: "/",
    Component: Layout,
    children: [
      { index: true, Component: () => <ProtectedRoute element={<Dashboard />} /> },
      { path: "transactions", Component: () => <ProtectedRoute element={<Transactions />} /> },
      { path: "gateways", Component: () => <ProtectedRoute element={<Gateways />} /> },
      { path: "settings", Component: () => <ProtectedRoute element={<Settings />} /> },
    ],
  },
]);
