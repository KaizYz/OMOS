import React, { useState, useEffect } from "react";
import axios from "axios";
import { useAuth } from "../../contexts/AuthContext";

const OrderManagement = () => {
  const { user } = useAuth();
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    fetchOrders();
    const interval = setInterval(fetchOrders, 30000); // Poll every 30 seconds
    return () => clearInterval(interval);
  }, []);

  const fetchOrders = async () => {
    try {
      const response = await axios.get("/api/order/list.php", {
        params: {
          user_id: user.id,
          role: user.role,
        },
      });
      setOrders(response.data.orders);
    } catch (err) {
      setError("Failed to fetch orders");
    } finally {
      setLoading(false);
    }
  };

  const updateOrderStatus = async (orderId, newStatus) => {
    try {
      await axios.post("/api/order/update-status.php", {
        order_id: orderId,
        status: newStatus,
      });
      fetchOrders(); // Refresh orders after update
    } catch (err) {
      setError("Failed to update order status");
    }
  };

  const renderOrderActions = (order) => {
    if (user.role === "restaurant_owner") {
      return (
        <div className="order-actions">
          {order.status === "pending" && (
            <button onClick={() => updateOrderStatus(order.id, "processing")}>
              Accept Order
            </button>
          )}
          {order.status === "processing" && (
            <button onClick={() => updateOrderStatus(order.id, "ready")}>
              Mark as Ready
            </button>
          )}
          {order.status === "ready" && (
            <button onClick={() => updateOrderStatus(order.id, "completed")}>
              Complete Order
            </button>
          )}
        </div>
      );
    }
    return null;
  };

  if (loading) return <div>Loading orders...</div>;
  if (error) return <div className="error-message">{error}</div>;

  return (
    <div className="order-management">
      <h2>
        {user.role === "restaurant_owner" ? "Restaurant Orders" : "My Orders"}
      </h2>
      <div className="orders-list">
        {orders.map((order) => (
          <div key={order.id} className={`order-card status-${order.status}`}>
            <div className="order-header">
              <h3>Order #{order.id}</h3>
              <span className="order-status">{order.status}</span>
            </div>
            <div className="order-details">
              <p>Total: ${order.total_amount}</p>
              <p>Date: {new Date(order.created_at).toLocaleString()}</p>
            </div>
            <div className="order-items">
              {order.items.map((item, index) => (
                <div key={index} className="order-item">
                  <span>{item.quantity}x</span>
                  <span>{item.name}</span>
                  <span>${item.subtotal}</span>
                </div>
              ))}
            </div>
            {renderOrderActions(order)}
          </div>
        ))}
      </div>
    </div>
  );
};

export default OrderManagement;
