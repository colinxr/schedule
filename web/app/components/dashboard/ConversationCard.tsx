import { cn } from "@/lib/utils"
import { Separator } from "@/components/ui/separator"
import { Badge } from "@/components/ui/badge"

interface ConversationCardProps {
  clientName: string
  lastMessage: string
  timestamp: string
  status: 'pending' | 'active' | 'closed'
  isSelected?: boolean
  onClick?: () => void
  showSeparator?: boolean
}

const statusColors = {
  pending: "bg-yellow-100 text-yellow-800",
  active: "bg-green-100 text-green-800",
  closed: "bg-gray-100 text-gray-800"
} as const;

export default function ConversationCard({
  clientName,
  lastMessage,
  timestamp,
  status,
  isSelected = false,
  onClick,
  showSeparator = true,
}: ConversationCardProps) {
  return (
    <>
      <div
        onClick={onClick}
        className={cn(
          "p-4 hover:bg-accent cursor-pointer transition-colors",
          isSelected && "bg-accent"
        )}
      >
        <div className="flex justify-between items-start">
          <div className="flex items-center gap-2">
            <span className="font-medium">{clientName}</span>
            <Badge variant="secondary" className={statusColors[status]}>
              {status}
            </Badge>
          </div>
          <span className="text-sm text-muted-foreground">{timestamp}</span>
        </div>
        <p className="text-sm text-muted-foreground truncate mt-1">
          {lastMessage}
        </p>
      </div>
      {showSeparator && <Separator />}
    </>
  )
} 