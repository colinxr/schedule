import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import RegisterPage from "@/app/auth/register/page";
import { act } from "react";

describe("RegisterPage", () => {
  it("renders registration form", () => {
    render(<RegisterPage />);
    expect(screen.getByText("Create an Account")).toBeInTheDocument();
    expect(screen.getByLabelText("First Name")).toBeInTheDocument();
    expect(screen.getByLabelText("Last Name")).toBeInTheDocument();
    expect(screen.getByLabelText("Email")).toBeInTheDocument();
    expect(screen.getByLabelText("Password")).toBeInTheDocument();
    expect(screen.getByLabelText("Confirm Password")).toBeInTheDocument();
    expect(screen.getByRole("button", { name: "Register" })).toBeInTheDocument();
  });

  it("validates required fields", async () => {
    render(<RegisterPage />);

    await act(async () => {
      fireEvent.submit(screen.getByRole("form"));
    });

    await waitFor(() => {
      expect(screen.getAllByText("Required")).toHaveLength(5);
    });
  });

  it("validates password requirements", async () => {
    render(<RegisterPage />);

    await act(async () => {
      const passwordInput = screen.getByLabelText("Password");
      
      fireEvent.change(passwordInput, { target: { value: "weak" } });
      fireEvent.submit(screen.getByRole("form"));
    });

    await waitFor(() => {
      expect(screen.getByText("Password must be at least 8 characters")).toBeInTheDocument();
    });

    await act(async () => {
      const passwordInput = screen.getByLabelText("Password");
      fireEvent.change(passwordInput, { target: { value: "weakpassword" } });
      fireEvent.submit(screen.getByRole("form"));
    });

    await waitFor(() => {
      expect(screen.getByText("Password must contain at least one uppercase letter")).toBeInTheDocument();
    });
  });

  it("validates password confirmation", async () => {
    render(<RegisterPage />);

    await act(async () => {
      // Fill in all required fields to avoid other validation errors
      fireEvent.change(screen.getByLabelText("First Name"), { target: { value: "John" } });
      fireEvent.change(screen.getByLabelText("Last Name"), { target: { value: "Doe" } });
      fireEvent.change(screen.getByLabelText("Email"), { target: { value: "john@example.com" } });
      fireEvent.change(screen.getByLabelText("Password"), { target: { value: "StrongPass123" } });
      fireEvent.change(screen.getByLabelText("Confirm Password"), { target: { value: "DifferentPass123" } });
      fireEvent.submit(screen.getByRole("form"));
    });

    await waitFor(() => {
      const errorMessage = screen.getByText("Passwords do not match");
      expect(errorMessage).toBeInTheDocument();
    });
  });

  it("handles successful registration", async () => {
    const mockRouter = { push: jest.fn() };
    jest.mock("next/navigation", () => ({
      useRouter: () => mockRouter,
    }));

    global.fetch = jest.fn().mockResolvedValueOnce({
      ok: true,
    });

    render(<RegisterPage />);

    await act(async () => {
      fireEvent.change(screen.getByLabelText("First Name"), { target: { value: "John" } });
      fireEvent.change(screen.getByLabelText("Last Name"), { target: { value: "Doe" } });
      fireEvent.change(screen.getByLabelText("Email"), { target: { value: "john@example.com" } });
      fireEvent.change(screen.getByLabelText("Password"), { target: { value: "StrongPass123" } });
      fireEvent.change(screen.getByLabelText("Confirm Password"), { target: { value: "StrongPass123" } });
      fireEvent.submit(screen.getByRole("form"));
    });
  });
}); 