import React, { useState } from "react";
import axios from "axios";
import { useAuth } from "../../contexts/AuthContext";

const PaymentForm = ({ orderAmount, onPaymentComplete }) => {
  const { user } = useAuth();
  const [cardData, setCardData] = useState({
    card_name: "",
    card_number: "",
    card_expiry: "",
    card_type: "visa",
  });
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError("");
    setLoading(true);

    try {
      // First save the payment method
      const paymentMethodResponse = await axios.post(
        "/api/payment/save-method.php",
        {
          ...cardData,
          user_id: user.id,
        }
      );

      if (!paymentMethodResponse.data.success) {
        throw new Error(paymentMethodResponse.data.error);
      }

      // Process the payment
      const paymentResponse = await axios.post("/api/payment/process.php", {
        user_id: user.id,
        payment_type_id: paymentMethodResponse.data.payment_type_id,
        total_amount: orderAmount,
      });

      if (!paymentResponse.data.success) {
        throw new Error(paymentResponse.data.error);
      }

      onPaymentComplete(paymentResponse.data);
    } catch (err) {
      setError(err.message || "Payment processing failed");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="payment-form">
      <h3>Payment Details</h3>
      {error && <div className="error-message">{error}</div>}
      <form onSubmit={handleSubmit}>
        <div className="form-group">
          <label>Card Holder Name:</label>
          <input
            type="text"
            value={cardData.card_name}
            onChange={(e) =>
              setCardData({ ...cardData, card_name: e.target.value })
            }
            required
          />
        </div>
        <div className="form-group">
          <label>Card Number:</label>
          <input
            type="text"
            value={cardData.card_number}
            onChange={(e) =>
              setCardData({ ...cardData, card_number: e.target.value })
            }
            pattern="\d{4}-\d{4}-\d{4}-\d{4}"
            placeholder="XXXX-XXXX-XXXX-XXXX"
            required
          />
        </div>
        <div className="form-group">
          <label>Expiry Date:</label>
          <input
            type="text"
            value={cardData.card_expiry}
            onChange={(e) =>
              setCardData({ ...cardData, card_expiry: e.target.value })
            }
            pattern="\d{2}/\d{2}"
            placeholder="MM/YY"
            required
          />
        </div>
        <div className="form-group">
          <label>Card Type:</label>
          <select
            value={cardData.card_type}
            onChange={(e) =>
              setCardData({ ...cardData, card_type: e.target.value })
            }
          >
            <option value="visa">Visa</option>
            <option value="mastercard">Mastercard</option>
            <option value="amex">American Express</option>
          </select>
        </div>
        <button type="submit" disabled={loading}>
          {loading ? "Processing..." : `Pay $${orderAmount}`}
        </button>
      </form>
    </div>
  );
};

export default PaymentForm;
