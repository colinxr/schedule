import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import LoginPage from "@/app/auth/login/page";
import { useRouter, useSearchParams } from "next/navigation";
import { act } from "react";

jest.mock("next/navigation", () => ({
  useRouter: jest.fn(),
  useSearchParams: jest.fn(),
}));

describe("LoginPage", () => {
  beforeEach(() => {
    (useSearchParams as jest.Mock).mockReturnValue(new URLSearchParams());
  });

  it("renders login form with all fields", () => {
    render(<LoginPage />);
    expect(screen.getByText("Welcome Back")).toBeInTheDocument();
    expect(screen.getByLabelText("Email")).toBeInTheDocument();
    expect(screen.getByLabelText("Password")).toBeInTheDocument();
    expect(screen.getByRole("button", { name: "Sign In" })).toBeInTheDocument();
  });

  it("shows success message when registered=true in URL", () => {
    const searchParams = new URLSearchParams();
    searchParams.set("registered", "true");
    (useSearchParams as jest.Mock).mockReturnValue(searchParams);

    render(<LoginPage />);
    expect(screen.getByText(/registration successful/i)).toBeInTheDocument();
  });

  it("validates required fields", async () => {
    render(<LoginPage />);

    await act(async () => {
      fireEvent.submit(screen.getByRole("form"));
    });

    await waitFor(() => {
      expect(screen.getByText("Please enter a valid email address")).toBeInTheDocument();
      expect(screen.getByText("Password is required")).toBeInTheDocument();
    });
  });

  it("validates email format", async () => {
    render(<LoginPage />);

    await act(async () => {
      fireEvent.change(screen.getByLabelText("Email"), {
        target: { value: "invalid-email" },
      });
      fireEvent.change(screen.getByLabelText("Password"), {
        target: { value: "password123" },
      });
      fireEvent.submit(screen.getByRole("form"));
    });

    await waitFor(() => {
      expect(screen.getByText("Please enter a valid email address")).toBeInTheDocument();
    });
  });

  it("submits form with valid data", async () => {
    const mockRouter = { push: jest.fn() };
    (useRouter as jest.Mock).mockReturnValue(mockRouter);

    global.fetch = jest.fn().mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve({}),
    });

    render(<LoginPage />);

    await act(async () => {
      fireEvent.change(screen.getByLabelText("Email"), {
        target: { value: "test@example.com" },
      });
      fireEvent.change(screen.getByLabelText("Password"), {
        target: { value: "password123" },
      });
      fireEvent.submit(screen.getByRole("form"));
    });

    await waitFor(() => {
      expect(mockRouter.push).toHaveBeenCalledWith("/dashboard");
    });
  });

  it("handles login error", async () => {
    const errorMessage = "Invalid credentials";
    global.fetch = jest.fn().mockResolvedValueOnce({
      ok: false,
      json: () => Promise.resolve({ message: errorMessage }),
    });

    render(<LoginPage />);

    await act(async () => {
      fireEvent.change(screen.getByLabelText("Email"), {
        target: { value: "test@example.com" },
      });
      fireEvent.change(screen.getByLabelText("Password"), {
        target: { value: "password123" },
      });
      fireEvent.submit(screen.getByRole("form"));
    });

    await waitFor(() => {
      expect(screen.getByRole("alert")).toHaveTextContent(errorMessage);
    });
  });
}); 