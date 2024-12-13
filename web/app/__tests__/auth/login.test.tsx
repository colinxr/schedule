import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import LoginPage from "@/app/auth/login/page";
import { useRouter, useSearchParams } from "next/navigation";
import { act } from "react";
import { AuthService } from "@/services/auth/AuthService";

jest.mock("next/navigation", () => ({
  useRouter: jest.fn(),
  useSearchParams: jest.fn(),
}));

jest.mock("@/services/auth/AuthService", () => {
  const mockInstance = {
    login: jest.fn(),
    setToken: jest.fn(),
  };

  return {
    AuthService: {
      getInstance: jest.fn(() => mockInstance),
    },
  };
});

describe("LoginPage", () => {
  const mockRouter = { push: jest.fn() };
  let mockAuthService: jest.Mocked<Partial<AuthService>>;

  beforeEach(() => {
    (useRouter as jest.Mock).mockReturnValue(mockRouter);
    (useSearchParams as jest.Mock).mockReturnValue(new URLSearchParams());
    mockRouter.push.mockClear();

    mockAuthService = (AuthService.getInstance() as jest.Mocked<Partial<AuthService>>);
    mockAuthService.login.mockResolvedValue({ data: { token: 'fake-token', user: {} } });

    // Mock localStorage
    Object.defineProperty(window, 'localStorage', {
      value: {
        getItem: jest.fn(),
        setItem: jest.fn(),
        removeItem: jest.fn(),
      },
      writable: true,
    });
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
    render(<LoginPage />);

    const validData = {
      email: "test@example.com",
      password: "password123",
    };

    await act(async () => {
      fireEvent.change(screen.getByLabelText("Email"), {
        target: { value: validData.email },
      });
      fireEvent.change(screen.getByLabelText("Password"), {
        target: { value: validData.password },
      });
      fireEvent.submit(screen.getByRole("form"));
    });

    await waitFor(() => {
      expect(mockAuthService.login).toHaveBeenCalledWith(validData);
      expect(localStorage.setItem).toHaveBeenCalledWith('token', 'fake-token');
      expect(mockRouter.push).toHaveBeenCalledWith("/dashboard");
    });
  });

  it("handles login error", async () => {
    const errorMessage = "Invalid credentials";
    mockAuthService.login = jest.fn().mockRejectedValue(new Error(errorMessage));

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