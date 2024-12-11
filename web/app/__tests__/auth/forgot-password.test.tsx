import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import ForgotPasswordPage from "@/app/auth/forgot-password/page";
import { act } from "react";

describe("ForgotPasswordPage", () => {
  beforeEach(() => {
    global.fetch = jest.fn();
  });

  it("renders forgot password form", () => {
    render(<ForgotPasswordPage />);
    expect(screen.getByText("Forgot Password")).toBeInTheDocument();
    expect(screen.getByLabelText("Email")).toBeInTheDocument();
    expect(screen.getByRole("button", { name: "Send Reset Link" })).toBeInTheDocument();
  });

  it("validates email format", async () => {
    render(<ForgotPasswordPage />);

    await act(async () => {
      const emailInput = screen.getByLabelText("Email");
      fireEvent.change(emailInput, { target: { value: "invalid-email" } });
      fireEvent.blur(emailInput);
    });

    await waitFor(() => {
      expect(screen.getByText("Please enter a valid email address")).toBeInTheDocument();
    });
  });

  it("shows success message after submitting valid email", async () => {
    (global.fetch as jest.Mock).mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve({ message: "Reset email sent" }),
    } as Response);

    render(<ForgotPasswordPage />);

    await act(async () => {
      const emailInput = screen.getByLabelText("Email");
      fireEvent.change(emailInput, { target: { value: "test@example.com" } });
      fireEvent.submit(screen.getByRole("form"));
    });

    await waitFor(() => {
      expect(screen.getByText(/If an account exists with that email address/)).toBeInTheDocument();
      expect(screen.getByText("Return to login")).toBeInTheDocument();
    });
  });

  it("handles API errors gracefully", async () => {
    const errorMessage = "Failed to send reset email";
    (global.fetch as jest.Mock).mockResolvedValueOnce({
      ok: false,
      json: () => Promise.resolve({ message: errorMessage }),
    } as Response);

    render(<ForgotPasswordPage />);

    await act(async () => {
      const emailInput = screen.getByLabelText("Email");
      fireEvent.change(emailInput, { target: { value: "test@example.com" } });
      fireEvent.submit(screen.getByRole("form"));
    });

    await waitFor(() => {
      const alert = screen.getByRole("alert");
      expect(alert).toHaveTextContent(errorMessage);
    });
  });
}); 