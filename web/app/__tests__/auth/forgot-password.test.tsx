import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import ForgotPasswordPage from "@/app/auth/forgot-password/page";
import { act } from "react";
import { AuthService } from "@/services/auth/AuthService";

jest.mock("@/services/auth/AuthService", () => {
  const mockInstance = {
    forgotPassword: jest.fn(),
  };

  return {
    AuthService: {
      getInstance: jest.fn(() => mockInstance),
    },
  };
});

describe("ForgotPasswordPage", () => {
  let mockAuthService: ReturnType<typeof AuthService.getInstance>;

  beforeEach(() => {
    mockAuthService = AuthService.getInstance();
    (mockAuthService.forgotPassword as jest.Mock).mockResolvedValue({ data: null });
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
    render(<ForgotPasswordPage />);

    const validEmail = "test@example.com";

    await act(async () => {
      fireEvent.change(screen.getByLabelText("Email"), {
        target: { value: validEmail },
      });
      fireEvent.submit(screen.getByRole("form"));
    });

    await waitFor(() => {
      expect(mockAuthService.forgotPassword).toHaveBeenCalledWith({ email: validEmail });
      expect(screen.getByText(/If an account exists with that email address/)).toBeInTheDocument();
      expect(screen.getByText("Return to login")).toBeInTheDocument();
    });
  });

  it("handles API errors gracefully", async () => {
    const errorMessage = "Failed to send reset email";
    (mockAuthService.forgotPassword as jest.Mock).mockRejectedValue(new Error(errorMessage));

    render(<ForgotPasswordPage />);

    await act(async () => {
      fireEvent.change(screen.getByLabelText("Email"), {
        target: { value: "test@example.com" },
      });
      fireEvent.submit(screen.getByRole("form"));
    });

    await waitFor(() => {
      expect(screen.getByRole("alert")).toHaveTextContent(errorMessage);
    });
  });
}); 